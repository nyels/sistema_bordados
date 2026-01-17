@extends('adminlte::page')

@section('title', 'Editar Unidad de Medida')

@section('content_header')
@stop

@section('content')
    <div class="container-fluid pt-3 pb-5">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">

                {{-- MENSAJES FLASH --}}
                @foreach (['success', 'error', 'info'] as $msg)
                    @if (session($msg))
                        <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }} alert-dismissible fade show shadow-sm mb-4"
                            role="alert">
                            <i class="{{ $msg == 'success' ? 'fas fa-check-circle' : 'fas fa-info-circle' }} mr-2"></i>
                            {{ session($msg) }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif
                @endforeach

                {{-- ERRORES DE VALIDACIÓN --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4">
                        <strong><i class="fas fa-exclamation-triangle mr-2"></i>Por favor corrige los siguientes
                            errores:</strong>
                        <ul class="mb-0 mt-2 pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                @endif

                @php
                    // Lógica de Bloqueo Centralizada en el Modelo
                    $isLocked = $unit->isSystemUnit();
                @endphp

                <form method="post" action="{{ route('admin.units.update', $unit->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="card shadow-lg border-0 rounded-lg overflow-hidden">
                        {{-- Encabezado Amarillo Estilo Premium --}}
                        <div class="card-header bg-warning text-dark py-3">
                            <h3 class="card-title font-weight-bold m-0" style="font-size: 1.2rem;">
                                <i class="fas fa-edit mr-2"></i> Editar Unidad de Medida
                            </h3>
                        </div>

                        <div class="card-body p-4 bg-light">
                            @include('admin.units._form')
                        </div>

                        <div class="card-footer bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.units.index') }}" class="btn btn-secondary shadow-sm">
                                <i class="fas fa-arrow-left mr-2"></i> Regresar
                            </a>
                            @if ($isLocked)
                                <button type="button" class="btn btn-warning shadow-sm px-4 font-weight-bold" disabled
                                    title="Esta unidad está protegida por el sistema">
                                    <i class="fas fa-lock mr-2"></i> Protegido
                                </button>
                            @else
                                <button type="submit" class="btn btn-warning shadow-sm px-4 font-weight-bold">
                                    <i class="fas fa-save mr-2"></i> Guardar
                                </button>
                            @endif
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .card {
            border-radius: 0.5rem;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
    </style>
@stop
