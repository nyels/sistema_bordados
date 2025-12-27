@extends('adminlte::page')

@section('title', 'Crear Exportación para Variante')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>
            <i class="fas fa-file-export text-primary mr-2"></i>
            Nueva Exportación de Bordado
        </h1>
        <a href="{{ route('admin.designs.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver a Diseños
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle mr-1"></i>
                            Exportación para variante: <strong>{{ $variant->name }}</strong>
                            <small class="text-muted">({{ $design->name }})</small>
                        </h3>
                    </div>
                    <form action="{{ route('admin.variants.exports.store', ['design' => $design->id, 'variant' => $variant->id]) }}"
                        method="POST" enctype="multipart/form-data" id="exportForm">
                        @csrf
                        <div class="card-body">
                            {{-- Errores de validación --}}
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><i class="icon fas fa-ban"></i> Error de validación</h5>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    {{-- Tipo de Aplicación (Select dinámico) --}}
                                    <div class="form-group">
                                        <label for="application_type">
                                            <i class="fas fa-tag mr-1"></i> Tipo de Aplicación
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control @error('application_type') is-invalid @enderror"
                                            id="application_type" name="application_type" required>
                                            <option value="">-- Seleccione un tipo --</option>
                                            @foreach ($applicationTypes as $tipo)
                                                <option value="{{ $tipo->slug }}"
                                                    {{ old('application_type') == $tipo->slug ? 'selected' : '' }}>
                                                    {{ $tipo->nombre_aplicacion }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('application_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">
                                            Ubicación donde se aplicará el bordado (pecho, espalda, manga, etc.)
                                        </small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    {{-- Etiqueta de Aplicación --}}
                                    <div class="form-group">
                                        <label for="application_label">
                                            <i class="fas fa-bookmark mr-1"></i> Etiqueta / Nombre
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                            class="form-control @error('application_label') is-invalid @enderror"
                                            id="application_label" name="application_label"
                                            value="{{ old('application_label') }}" required
                                            placeholder="Ej: Logo frontal pequeño">
                                        @error('application_label')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">
                                            Nombre descriptivo para identificar esta exportación
                                        </small>
                                    </div>
                                </div>
                            </div>

                            {{-- Descripción de ubicación --}}
                            <div class="form-group">
                                <label for="placement_description">
                                    <i class="fas fa-map-marker-alt mr-1"></i> Descripción de Ubicación
                                </label>
                                <input type="text"
                                    class="form-control @error('placement_description') is-invalid @enderror"
                                    id="placement_description" name="placement_description"
                                    value="{{ old('placement_description') }}"
                                    placeholder="Ej: Centrado a 5cm del cuello, lado izquierdo">
                                @error('placement_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Detalles adicionales sobre la posición exacta (opcional)
                                </small>
                            </div>

                            {{-- Archivo de Bordado --}}
                            <div class="form-group">
                                <label for="file">
                                    <i class="fas fa-file-upload mr-1"></i> Archivo de Bordado
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input @error('file') is-invalid @enderror"
                                        id="file" name="file" accept=".pes,.dst,.exp,.jef,.vip,.vp3,.xxx" required>
                                    <label class="custom-file-label" for="file" data-browse="Buscar">
                                        Seleccionar archivo...
                                    </label>
                                </div>
                                @error('file')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Formatos permitidos: <strong>PES, DST, EXP, JEF, VIP, VP3, XXX</strong> (máx. 10MB)
                                </small>
                            </div>

                            {{-- Notas --}}
                            <div class="form-group">
                                <label for="notes">
                                    <i class="fas fa-sticky-note mr-1"></i> Notas adicionales
                                </label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes"
                                    rows="3" placeholder="Observaciones, instrucciones especiales...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Guardar Exportación
                            </button>
                            <a href="{{ route('admin.designs.index') }}" class="btn btn-default">
                                <i class="fas fa-times mr-1"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Panel lateral con info de la variante --}}
            <div class="col-md-4">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-layer-group mr-1"></i> Variante
                        </h3>
                    </div>
                    <div class="card-body text-center">
                        @php
                            $variantImage = $variant->images()->orderBy('order')->first();
                        @endphp
                        @if ($variantImage)
                            <img src="{{ asset('storage/' . $variantImage->file_path) }}"
                                alt="{{ $variant->name }}" class="img-fluid rounded mb-3"
                                style="max-height: 200px; object-fit: contain;">
                        @elseif ($design->primaryImage)
                            <img src="{{ asset('storage/' . $design->primaryImage->file_path) }}"
                                alt="{{ $design->name }}" class="img-fluid rounded mb-3"
                                style="max-height: 200px; object-fit: contain; opacity: 0.6;">
                            <p class="text-muted small">Imagen del diseño principal</p>
                        @else
                            <div class="text-muted py-4">
                                <i class="fas fa-image fa-3x mb-2"></i>
                                <p>Sin imagen</p>
                            </div>
                        @endif
                        <h5>{{ $variant->name }}</h5>
                        <p class="text-muted small">
                            Diseño: {{ $design->name }} ({{ $design->code }})
                        </p>
                        @if ($variant->description)
                            <p class="small">{{ Str::limit($variant->description, 100) }}</p>
                        @endif
                    </div>
                </div>

                {{-- Info de formatos --}}
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-question-circle mr-1"></i> Formatos de Bordado
                        </h3>
                    </div>
                    <div class="card-body small">
                        <ul class="list-unstyled mb-0">
                            <li><strong>.PES</strong> - Brother/Babylock</li>
                            <li><strong>.DST</strong> - Tajima</li>
                            <li><strong>.EXP</strong> - Melco</li>
                            <li><strong>.JEF</strong> - Janome</li>
                            <li><strong>.VIP/.VP3</strong> - Pfaff/Viking</li>
                            <li><strong>.XXX</strong> - Singer</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .custom-file-label::after {
            content: "Buscar";
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Mostrar nombre del archivo seleccionado
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            });
        });
    </script>
@stop
