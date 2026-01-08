@extends('adminlte::page')

@section('title', 'Detalles de Producción')

@section('content_header')
    <h1>Detalles de Producción #{{ $export->id }}</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    Diseño: <strong>{{ $export->design->name ?? 'N/A' }}</strong>
                </h3>
                <span
                    class="badge badge-{{ $export->status == 'aprobado' ? 'success' : ($export->status == 'pendiente' ? 'warning' : 'secondary') }}">
                    {{ ucfirst($export->status) }}
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>SKU:</strong> {{ $export->design->sku ?? 'N/A' }}<br>
                        <strong>Creado por:</strong> {{ $export->creator->name ?? 'N/A' }}<br>
                        <strong>Fecha:</strong> {{ strtoupper($export->created_at->translatedFormat('d M Y - H:i')) }}<br>
                        <hr>
                        <strong>Notas:</strong>
                        <p>{{ $export->notes ?? 'Sin notas.' }}</p>
                    </div>
                    <div class="col-md-6">
                        @if ($export->file_path)
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-file"></i> Archivo Adjunto</h5>
                                Nombre: {{ $export->file_name }} <br>
                                Tamaño: {{ round($export->file_size / 1024, 2) }} KB <br>
                                {{-- Link to download/view if public --}}
                                <a href="{{ asset('storage/' . $export->file_path) }}" target="_blank"
                                    class="btn btn-light mt-2">
                                    <i class="fas fa-download"></i> Descargar / Ver
                                </a>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                No hay archivo adjunto.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.production.index') }}" class="btn btn-secondary">Volver</a>
                <a href="{{ route('admin.production.edit', $export->id) }}" class="btn btn-primary">Editar</a>
            </div>
        </div>
    </div>
@stop
