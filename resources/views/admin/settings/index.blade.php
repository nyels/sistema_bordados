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
            {{-- TABS DE GRUPOS (sin recargar página) --}}
            <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                @foreach ($groups as $index => $group)
                    <li class="nav-item">
                        <a class="nav-link {{ $index === 0 ? 'active' : '' }}"
                           id="tab-{{ $group }}"
                           data-toggle="tab"
                           href="#content-{{ $group }}"
                           role="tab"
                           aria-controls="content-{{ $group }}"
                           aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
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

            {{-- CONTENIDO DE TABS --}}
            <div class="tab-content" id="settingsTabContent">
                @foreach ($groups as $index => $group)
                    <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                         id="content-{{ $group }}"
                         role="tabpanel"
                         aria-labelledby="tab-{{ $group }}">

                        {{-- FORMULARIO POR GRUPO --}}
                        <form method="POST" action="{{ route('admin.settings.bulk-update') }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="group" value="{{ $group }}">

                            <div class="row">
                                @php
                                    $groupSettings = $allSettings->where('group', $group);
                                @endphp

                                @forelse ($groupSettings as $setting)
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

                            @if ($groupSettings->count() > 0)
                                <hr>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Configuración
                                    </button>
                                </div>
                            @endif
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .nav-tabs .nav-link {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .nav-tabs .nav-link:hover {
            background-color: #f8f9fa;
        }

        .nav-tabs .nav-link.active {
            font-weight: 600;
        }

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

        .tab-pane {
            animation: fadeIn 0.2s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
                        return;
                    if ($(e.target).is(fileInput))
                        return;
                    fileInput.trigger('click');
                });

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
                        fileInput[0].files = files;
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
                    e.stopPropagation();
                    e.preventDefault();

                    zone.find('.dz-message').removeClass('d-none');
                    zone.find('.dz-preview').addClass('d-none');
                    zone.find('.file-name').text('');
                    zone.find('.img-preview').attr('src', '');
                    fileInput.val('');
                    hiddenInput.val('__DELETE__');
                });
            });

            function handleFile(file, zone, key) {
                if (!file.type.startsWith('image/')) {
                    Swal.fire('Error', 'Solo se permiten imágenes (JPG, PNG, WEBP)', 'error');
                    return;
                }

                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire('Error', 'La imagen no debe pesar más de 2MB', 'error');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    zone.find('.img-preview').attr('src', e.target.result);
                    zone.find('.filename').text(file.name);
                    zone.find('.dz-message').addClass('d-none');
                    zone.find('.dz-preview').removeClass('d-none');
                    zone.find('#hidden_' + key).val(file.name);
                }
                reader.readAsDataURL(file);
            }

            // Guardar pestaña activa en localStorage
            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                localStorage.setItem('activeSettingsTab', $(e.target).attr('href'));
            });

            // Restaurar pestaña activa al cargar
            const activeTab = localStorage.getItem('activeSettingsTab');
            if (activeTab) {
                $(`#settingsTabs a[href="${activeTab}"]`).tab('show');
            }
        });
    </script>
@stop
