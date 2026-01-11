@extends('adminlte::page')

@section('title', 'Eliminar Unidad de Medida')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="row">
        <div class="col-12 col-md-6 col-lg-4">

            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trash mr-2"></i>Eliminar Unidad de Medida
                    </h3>
                </div>

                <div class="card-body">
                    <form method="post" action="{{ route('admin.units.destroy', $unit->id) }}">
                        @csrf
                        @method('DELETE')

                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" class="form-control" value="{{ $unit->name }}" disabled>
                        </div>

                        <div class="form-group">
                            <label>Símbolo</label>
                            <input type="text" class="form-control" value="{{ $unit->symbol }}" disabled>
                        </div>

                        <div class="form-group">
                            <label>Tipo</label>
                            <input type="text" class="form-control"
                                value="{{ $unit->is_base ? 'Base (Consumo)' : 'Compra' }}" disabled>
                        </div>

                        <div class="form-group">
                            <label>Compatible con</label>
                            <input type="text" class="form-control"
                                value="{{ $unit->is_base ? '-' : $unit->compatibleBaseUnit->name ?? 'Sin configurar' }}"
                                disabled>
                        </div>

                        <hr>

                        @if ($unit->is_base && $unit->purchaseUnits->count() > 0)
                            {{-- ADVERTENCIA CRÍTICA: Unidad base con unidades de compra vinculadas --}}
                            <div class="alert alert-danger mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-radiation fa-2x mr-3 text-danger"></i>
                                    <strong class="h5 mb-0">ADVERTENCIA CRÍTICA</strong>
                                </div>
                                <hr class="my-2">
                                <p class="mb-2">
                                    Esta unidad base tiene <strong>{{ $unit->purchaseUnits->count() }}</strong>
                                    unidad(es) de compra vinculada(s) que <strong>también serán eliminadas</strong>:
                                </p>
                                <ul class="mb-2">
                                    @foreach ($unit->purchaseUnits as $purchaseUnit)
                                        <li><strong>{{ $purchaseUnit->name }}</strong> ({{ $purchaseUnit->symbol }})</li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Esto afectará la estabilidad del sistema:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Conversiones de materiales configuradas</li>
                                    <li>Registros históricos de compras</li>
                                    <li>Cálculos de inventario y stock</li>
                                    <li>Reportes y estadísticas relacionadas</li>
                                </ul>
                            </div>
                        @else
                            {{-- ADVERTENCIA NORMAL: Unidad sin dependencias --}}
                            <div class="alert alert-warning text-center mb-3">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>¿Deseas eliminar esta unidad de medida?</strong>
                                <p class="text-muted small mb-0 mt-2">Esta acción no se puede deshacer.</p>
                            </div>
                        @endif

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="text-right">
                                    <a href="{{ route('admin.units.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left mr-1"></i> Regresar
                                    </a>
                                    <button type="button" class="btn btn-danger btn-confirm-delete">
                                        <i class="fas fa-trash mr-1"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
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
                @if ($unit->is_base && $unit->purchaseUnits->count() > 0)
                    Swal.fire({
                        title: '¿Confirmar eliminación?',
                        text: 'Se eliminará {{ $unit->name }} y {{ $unit->purchaseUnits->count() }} unidad(es) vinculada(s)',
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
                @else
                    Swal.fire({
                        title: '¿Confirmar eliminación?',
                        text: 'Se eliminará {{ $unit->name }}',
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
