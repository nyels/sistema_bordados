@extends('adminlte::page')

@section('title', 'Configuración del Sistema')

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

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-cogs"></i> CONFIGURACIÓN DEL SISTEMA
            </h3>
        </div>

        <div class="card-body">
            {{-- TABS DE GRUPOS --}}
            <ul class="nav nav-tabs mb-4">
                @foreach ($groups as $group)
                    <li class="nav-item">
                        <a class="nav-link {{ $activeGroup === $group ? 'active' : '' }}"
                            href="{{ route('admin.settings.index', ['group' => $group]) }}">
                            @switch($group)
                                @case('general')
                                    <i class="fas fa-home"></i> General
                                @break

                                @case('inventario')
                                    <i class="fas fa-boxes"></i> Inventario
                                @break

                                @case('facturacion')
                                    <i class="fas fa-file-invoice-dollar"></i> Facturación
                                @break

                                @case('produccion')
                                    <i class="fas fa-industry"></i> Producción
                                @break

                                @default
                                    <i class="fas fa-cog"></i> {{ ucfirst($group) }}
                            @endswitch
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- FORMULARIO DE CONFIGURACIONES --}}
            <form method="POST" action="{{ route('admin.settings.bulk-update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="group" value="{{ $activeGroup }}">

                <div class="row">
                    @forelse ($settings as $setting)
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="setting_{{ $setting->key }}">
                                    {{ $setting->label }}
                                </label>

                                @switch($setting->type)
                                    @case('boolean')
                                        <div class="custom-control custom-switch">
                                            <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                            <input type="checkbox" class="custom-control-input" id="setting_{{ $setting->key }}"
                                                name="settings[{{ $setting->key }}]" value="1"
                                                {{ old('settings.' . $setting->key, $setting->value) == '1' ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="setting_{{ $setting->key }}">
                                                Activado
                                            </label>
                                        </div>
                                    @break

                                    @case('select')
                                        <select class="form-control" id="setting_{{ $setting->key }}"
                                            name="settings[{{ $setting->key }}]">
                                            @foreach ($setting->options ?? [] as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}"
                                                    {{ old('settings.' . $setting->key, $setting->value) === $optionValue ? 'selected' : '' }}>
                                                    {{ $optionLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @break

                                    @case('integer')
                                        <input type="number" class="form-control" id="setting_{{ $setting->key }}"
                                            name="settings[{{ $setting->key }}]"
                                            value="{{ old('settings.' . $setting->key, $setting->value) }}" min="0">
                                    @break

                                    @case('image')
                                        <div class="setting-dropzone" id="dropzone_{{ $setting->key }}">
                                            <input type="file" name="settings[{{ $setting->key }}]"
                                                id="file_{{ $setting->key }}" class="d-none file-input"
                                                accept="image/png, image/jpeg, image/jpg, image/webp"
                                                data-key="{{ $setting->key }}">

                                            <!-- Input oculto para manejar el borrado -->
                                            <!-- Si el usuario borra la imagen, este input enviará __DELETE__ -->
                                            <!-- Si no se toca, enviará el valor actual (o vacío si no hay) -->
                                            <!-- Pero como file input no retiene valor, necesitamos lógica JS para esto -->
                                            <input type="hidden" name="settings[{{ $setting->key }}]"
                                                id="hidden_{{ $setting->key }}"
                                                value="{{ old('settings.' . $setting->key, $setting->value) }}">

                                            <div class="dz-message {{ $setting->value ? 'd-none' : '' }}">
                                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                                <h5>Arrastra tu imagen aquí</h5>
                                                <p class="text-muted small mb-0">o haz clic para seleccionar</p>
                                                <p class="text-xs text-muted mt-1">(PNG, JPG, WEBP - Max 2MB)</p>
                                            </div>

                                            <div class="dz-preview {{ $setting->value ? '' : 'd-none' }}">
                                                <div class="preview-container">
                                                    @if ($setting->value)
                                                        <img src="{{ Storage::url($setting->value) }}" class="img-preview"
                                                            id="preview_{{ $setting->key }}">
                                                    @else
                                                        <img src="" class="img-preview" id="preview_{{ $setting->key }}">
                                                    @endif
                                                    <div class="preview-overlay">
                                                        <button type="button" class="btn btn-danger btn-circle btn-remove"
                                                            data-key="{{ $setting->key }}">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @break

                                    @default
                                        <input type="text" class="form-control" id="setting_{{ $setting->key }}"
                                            name="settings[{{ $setting->key }}]"
                                            value="{{ old('settings.' . $setting->key, $setting->value) }}" maxlength="255">
                                @endswitch

                                @if ($setting->description)
                                    <small class="form-text text-muted">{{ $setting->description }}</small>
                                @endif
                            </div>
                        </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No hay configuraciones en este grupo.
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if ($settings->count() > 0)
                        <hr>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    @stop

    @section('css')
        <style>
            .setting-dropzone {
                border: 2px dashed #cbd5e1;
                border-radius: 12px;
                background: #f8fafc;
                min-height: 200px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .setting-dropzone:hover,
            .setting-dropzone.dragover {
                border-color: #3b82f6;
                background: #eff6ff;
            }

            .dz-message {
                text-align: center;
                pointer-events: none;
            }

            .preview-container {
                position: relative;
                display: inline-block;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                background: white;
                /* Para transparencias en PNG/WEBP */
            }

            .img-preview {
                max-height: 180px;
                max-width: 100%;
                display: block;
                transition: transform 0.3s ease;
            }

            .preview-container:hover .img-preview {
                transform: scale(1.05);
            }

            .preview-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: opacity 0.2s ease;
            }

            .preview-container:hover .preview-overlay {
                opacity: 1;
            }

            .btn-circle {
                width: 40px;
                height: 40px;
                padding: 0;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 5px;
            }
        </style>
    @stop

    @section('js')
        <script>
            $(document).ready(function() {
                // Manejo global para todas las dropzones
                $('.setting-dropzone').each(function() {
                    const zone = $(this);
                    const key = zone.find('.file-input').data('key');
                    const fileInput = zone.find('#file_' + key);
                    const hiddenInput = zone.find('#hidden_' + key);
                    const removeBtn = zone.find('.btn-remove');

                    // Click en zona -> Click en input
                    zone.on('click', function(e) {
                        if ($(e.target).closest('.btn-remove').length)
                            return; // Si es botón eliminar, no abrir
                        if ($(e.target).is(fileInput))
                            return; // Evitar recursión si el evento viene del input
                        fileInput.trigger('click');
                    });

                    // Detener propagación del click en el input para seguridad extra
                    fileInput.on('click', function(e) {
                        e.stopPropagation();
                    });

                    // Drag & Drop events
                    zone.on('dragover dragenter', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        zone.addClass('dragover');
                    });

                    zone.on('dragleave drop', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        zone.removeClass('dragover');
                    });

                    zone.on('drop', function(e) {
                        const files = e.originalEvent.dataTransfer.files;
                        if (files.length > 0) {
                            fileInput[0].files = files; // Asignar al input
                            handleFile(files[0], zone, key);
                        }
                    });

                    // Change input
                    fileInput.on('change', function() {
                        if (this.files.length > 0) {
                            handleFile(this.files[0], zone, key);
                        }
                    });

                    // Remove image
                    removeBtn.on('click', function(e) {
                        e.stopPropagation(); // Evitar abrir file explorer
                        e.preventDefault();

                        // Limpiar preview
                        zone.find('.dz-message').removeClass('d-none');
                        zone.find('.dz-preview').addClass('d-none');
                        zone.find('.file-name').text('');
                        zone.find('.img-preview').attr('src', '');

                        // Limpiar input file
                        fileInput.val('');

                        // Marcar para borrado en backend
                        hiddenInput.val('__DELETE__');
                        // Necesitamos asegurar que el input file no se mande si está vacío, 
                        // o que el backend priorice el hiddenInput si tiene __DELETE__
                        // El controller ya tiene lógica para esto.
                    });
                });

                function handleFile(file, zone, key) {
                    // Validar imagen
                    if (!file.type.startsWith('image/')) {
                        Swal.fire('Error', 'Solo se permiten imágenes (JPG, PNG, WEBP)', 'error');
                        return;
                    }

                    if (file.size > 2 * 1024 * 1024) { // 2MB
                        Swal.fire('Error', 'La imagen no debe pesar más de 2MB', 'error');
                        return;
                    }

                    // Preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        zone.find('.img-preview').attr('src', e.target.result);
                        zone.find('.filename').text(file.name);

                        zone.find('.dz-message').addClass('d-none');
                        zone.find('.dz-preview').removeClass('d-none');

                        // Limpiar el flag de borrado si existía
                        zone.find('#hidden_' + key).val(file
                            .name); // Valor temporal, no se usa en backend si va file
                    }
                    reader.readAsDataURL(file);
                }
            });
        </script>
    @stop
