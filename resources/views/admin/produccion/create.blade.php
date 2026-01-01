@extends('adminlte::page')

@section('title', 'Nueva Producción')

@section('plugins.Select2', true)

@section('content_header')
    <h1>Nueva Producción</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Registrar Nueva Producción</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.produccion.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="design_id">Diseño</label>
                        <select name="design_id" id="design_id" class="form-control select2" style="width: 100%;">
                            <option value="">Seleccione un diseño...</option>
                            @foreach ($designs as $design)
                                <option value="{{ $design->id }}">
                                    {{ $design->name }} ({{ $design->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="file">Archivo de Bordado (Opcional)</label>
                        <input type="file" name="file" id="file" class="form-control-file">
                        <small class="form-text text-muted">Suba el archivo .dst, .emb, o similar si dispone de él.</small>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notas / Observaciones</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Detalles adicionales..."></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar Producción
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
                theme: 'bootstrap4',
                placeholder: 'Seleccione un diseño...'
            });
        });
    </script>
@stop
