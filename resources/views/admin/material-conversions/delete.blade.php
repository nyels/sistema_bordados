@extends('adminlte::page')

@section('title', 'Eliminar Conversión - ' . $material->name)

@section('content_header')
@stop

@section('content')
    <br>

    {{-- BREADCRUMB INFO --}}
    <div class="card bg-light mb-3">
        <div class="card-body py-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 bg-transparent p-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.materials.index') }}">
                            <i class="fas fa-boxes"></i> Materiales
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.material-conversions.index', $material->id) }}">
                            {{ $material->name }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Eliminar Conversión</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card card-danger">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-trash"></i> ELIMINAR CONVERSIÓN DE UNIDAD
            </h3>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.material-conversions.destroy', [$material->id, $conversion->id]) }}">
                @csrf
                @method('DELETE')

                <div class="row">
                    {{-- INFO --}}
                    <div class="col-md-6 offset-md-3">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <strong><i class="fas fa-exchange-alt"></i> Conversión a Eliminar</strong>
                            </div>
                            <div class="card-body text-center py-4">
                                <p class="mb-2"><strong>Material:</strong> {{ $material->name }}</p>
                                <hr>
                                <h4 class="mb-3">
                                    <span class="badge badge-secondary">
                                        1 {{ $conversion->fromUnit->name ?? 'N/A' }}
                                    </span>
                                    <i class="fas fa-arrow-right mx-2 text-primary"></i>
                                    <span class="badge badge-info">
                                        {{ number_format($conversion->conversion_factor, 2) }}
                                        {{ $conversion->toUnit->symbol ?? '' }}
                                    </span>
                                </h4>
                                <p class="text-muted mb-0">
                                    <code>{{ $conversion->display_conversion }}</code>
                                </p>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Advertencia:</strong><br>
                            Al eliminar esta conversión, no podrá registrar compras de este material
                            usando la unidad <strong>{{ $conversion->fromUnit->name ?? 'N/A' }}</strong>.
                            <br><br>
                            Las compras anteriores no se verán afectadas.
                        </div>
                    </div>
                </div>

                <div style="font-weight: bold;font-size: 25px;text-align: center;padding: 20px;">
                    ¿Deseas eliminar esta conversión?
                </div>

                <div class="text-center">
                    <a href="{{ route('admin.material-conversions.index', $material->id) }}" class="btn btn-secondary">
                        <i class="fas fa-times-circle"></i> Regresar
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop
