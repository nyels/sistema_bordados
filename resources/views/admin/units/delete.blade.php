@extends('adminlte::page')

@section('title', 'Eliminar Unidad de Medida')

@section('content_header')
@stop

@section('content')
    <div class="container-fluid pt-3 pb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6"> <!-- Tamaño reducido para eliminar/confirmación -->

                @php
                    // Lógica de Bloqueo Centralizada en el Modelo
                    $isLocked = $unit->isSystemUnit();
                @endphp

                <form method="post" action="{{ route('admin.units.destroy', $unit->id) }}">
                    @csrf
                    @method('DELETE')

                    <div class="card shadow-lg border-0 rounded-lg overflow-hidden">
                        {{-- Encabezado Rojo Estilo Danger --}}
                        <div class="card-header bg-danger text-white py-3">
                            <h3 class="card-title font-weight-bold m-0" style="font-size: 1.2rem;">
                                <i class="fas fa-trash-alt mr-2"></i> Eliminar Unidad de Medida
                            </h3>
                        </div>

                        <div class="card-body p-4 bg-light">

                            {{-- Resumen de la Unidad --}}
                            <div class="bg-white p-3 rounded shadow-sm mb-4 border">
                                <h5 class="text-secondary font-weight-bold mb-3 border-bottom pb-2">Resumen de la Unidad
                                </h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="font-weight-bold small text-muted text-uppercase">Nombre</label>
                                        <div class="h5 text-dark font-weight-bold">{{ $unit->name }}</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="font-weight-bold small text-muted text-uppercase">Símbolo</label>
                                        <div class="h5 text-dark font-weight-bold">{{ $unit->symbol }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="font-weight-bold small text-muted text-uppercase">Tipo</label>
                                        <div>
                                            @if ($unit->unit_type)
                                                <span class="badge badge-{{ $unit->unit_type->badgeColor() }} p-2"
                                                    style="font-size: 0.9rem;">
                                                    <i class="fas {{ $unit->unit_type->icon() }} mr-1"></i>
                                                    {{ $unit->unit_type->label() }}
                                                </span>
                                            @else
                                                <span class="badge badge-secondary p-2">Sin tipo</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($isLocked)
                                {{-- BLOQUEO DEL SISTEMA --}}
                                <div class="alert alert-danger shadow-sm border-danger text-center">
                                    <i class="fas fa-shield-alt fa-3x mb-3 text-danger"></i>
                                    <h5 class="font-weight-bold">ACCIÓN PROTEGIDA</h5>
                                    <p class="mb-0">
                                        Esta es una <strong>Unidad Fundamental del Sistema</strong>.
                                        <br>
                                        Por seguridad e integridad de los datos, <strong>no puede ser eliminada</strong>.
                                    </p>
                                </div>
                            @elseif ($unit->isCanonical() && $unit->purchaseUnits->count() > 0)
                                {{-- ADVERTENCIA CRÍTICA: Unidad de consumo con unidades vinculadas --}}
                                <div class="alert alert-danger shadow-sm border-danger">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-white rounded-circle p-2 text-danger mr-3">
                                            <i class="fas fa-radiation fa-2x"></i>
                                        </div>
                                        <div>
                                            <h5 class="font-weight-bold mb-0">¡ADVERTENCIA CRÍTICA!</h5>
                                            <small>Eliminación en cascada detectada</small>
                                        </div>
                                    </div>

                                    <p class="mb-3">
                                        Esta unidad de consumo tiene <strong>{{ $unit->purchaseUnits->count() }}</strong>
                                        unidad(es) de compra/presentación vinculada(s) que <strong>TAMBIÉN SERÁN
                                            ELIMINADAS</strong>:
                                    </p>
                                    <ul class="mb-3 bg-white rounded p-3 text-danger border border-danger">
                                        @foreach ($unit->purchaseUnits as $purchaseUnit)
                                            <li><strong>{{ $purchaseUnit->name }}</strong> ({{ $purchaseUnit->symbol }})
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="callout callout-warning bg-white mb-0">
                                        <h6 class="text-warning font-weight-bold"><i
                                                class="fas fa-exclamation-triangle mr-1"></i> Impacto en el Sistema:</h6>
                                        <ul class="small text-muted pl-3 mb-0">
                                            <li>Conversiones de materiales configuradas</li>
                                            <li>Registros históricos de compras</li>
                                            <li>Cálculos de inventario y stock</li>
                                            <li>Reportes y estadísticas relacionadas</li>
                                        </ul>
                                    </div>
                                </div>
                            @else
                                {{-- ADVERTENCIA NORMAL: Unidad sin dependencias --}}
                                <div class="alert alert-warning text-center border-warning shadow-sm">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2 text-warning"></i>
                                    <h5 class="font-weight-bold">¿Estás seguro?</h5>
                                    <p class="mb-0">
                                        Vas a eliminar la unidad <strong>{{ $unit->name }}</strong>.
                                        <br>
                                        <span class="text-muted small">Esta acción no se puede deshacer.</span>
                                    </p>
                                </div>
                            @endif

                        </div>

                        <div class="card-footer bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.units.index') }}" class="btn btn-secondary shadow-sm">
                                <i class="fas fa-arrow-left mr-2"></i> Regresar
                            </a>

                            @if (!$isLocked)
                                <button type="button"
                                    class="btn btn-danger shadow-sm px-4 font-weight-bold btn-confirm-delete">
                                    <i class="fas fa-trash-alt mr-2"></i> Confirmar Eliminación
                                </button>
                            @else
                                <button type="button" class="btn btn-light shadow-sm px-4 font-weight-bold" disabled>
                                    <i class="fas fa-lock mr-2"></i> Sistema
                                </button>
                            @endif
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function() {
            $('.btn-confirm-delete').on('click', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                @if ($unit->isCanonical() && $unit->purchaseUnits->count() > 0)
                    Swal.fire({
                        title: '¿Eliminación Crítica?',
                        html: 'Se eliminará <b>{{ $unit->name }}</b> y todas sus <b>{{ $unit->purchaseUnits->count() }} dependencias</b>.<br>¡Esto puede romper datos históricos!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-trash mr-1"></i> Sí, eliminar todo',
                        cancelButtonText: '<i class="fas fa-times mr-1"></i> Cancelar',
                        reverseButtons: true,
                        focusCancel: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                @else
                    Swal.fire({
                        title: '¿Confirmar eliminación?',
                        text: 'Se eliminará permanentemente la unidad {{ $unit->name }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-trash mr-1"></i> Sí, eliminar',
                        cancelButtonText: '<i class="fas fa-times mr-1"></i> Cancelar',
                        reverseButtons: true,
                        focusCancel: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                @endif
            });
        });
    </script>
@stop
