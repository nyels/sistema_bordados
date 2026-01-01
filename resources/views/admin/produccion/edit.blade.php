@extends('adminlte::page')

@section('title', 'Editar Producción')

@section('plugins.Select2', true)

@section('content_header')
    <h1>Editar Producción #{{ $export->id }}</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Editar Información</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.produccion.update', $export->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="design_id">Diseño</label>
                        {{-- Assuming we pass $designs to edit view as well. Controller needs update --}}
                        <select name="design_id" id="design_id" class="form-control select2" style="width: 100%;">
                            {{-- We need designs list here. Controller update required --}}
                            <option value="{{ $export->design_id }}" selected>
                                {{ $export->design->name ?? 'Diseño actual' }} ({{ $export->design->sku ?? '' }})
                            </option>
                        </select>
                        <small class="text-muted">El diseño no se puede cambiar fácilmente en esta vista simplificada. (Para
                            cambiar, cree una nueva).</small>
                    </div>

                    <div class="form-group">
                        <label for="file">Archivo de Bordado (Reemplazar)</label>
                        <input type="file" name="file" id="file" class="form-control-file">
                        @if ($export->file_path)
                            <small class="d-block mt-1">Archivo actual: {{ $export->file_name }}</small>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="notes">Notas / Observaciones</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3">{{ $export->notes }}</textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Actualizar
                        </button>
                        <a href="{{ route('admin.produccion.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap4'
            });
        });
    </script>
@stop
