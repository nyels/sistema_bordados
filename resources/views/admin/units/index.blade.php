@extends('adminlte::page')

@section('title', 'Catálogo de Unidades')

@section('content_header')
@stop

@section('content')
    <br>

    {{-- MENSAJES FLASH --}}
    @foreach (['success', 'error', 'info', 'warning'] as $msg)
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
                <i class="fas fa-ruler-combined"></i> CATÁLOGO DE UNIDADES
            </h3>
        </div>

        <div class="card-body">
            {{-- BOTÓN NUEVA UNIDAD --}}
            <div class="row mb-3">
                <div class="col-12">
                    <a href="{{ route('admin.units.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Unidad
                    </a>
                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- SECCIÓN: UNIDADES DE INVENTARIO (CANONICAL) --}}
            {{-- ================================================================ --}}
            <div class="card card-success card-outline mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-warehouse text-success"></i>
                        <strong>Unidades de Inventario</strong>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border-left border-success" style="border-left-width: 4px !important;">
                        <i class="fas fa-info-circle text-success"></i>
                        <strong>El sistema controla existencias y calcula costos en estas unidades.</strong>
                        <br>
                        <small class="text-muted">Ejemplos: Metro, Pieza, Litro, Kilogramo</small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 25%;">Nombre</th>
                                    <th style="width: 10%;">Símbolo</th>
                                    <th style="width: 35%;">Uso actual</th>
                                    <th style="width: 15%;">Estado</th>
                                    <th style="width: 10%;" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $inventoryUnits = $units->where('unit_type', \App\Enums\UnitType::CANONICAL);
                                    $counter = 1;
                                @endphp
                                @forelse ($inventoryUnits as $unit)
                                    @php
                                        $usage = $unit->getUsageInfo();
                                        $isLocked = $unit->isSystemUnit() || $usage['in_use'];
                                    @endphp
                                    <tr>
                                        <td>{{ $counter++ }}</td>
                                        <td>
                                            <strong>{{ $unit->name }}</strong>
                                            @if ($unit->isSystemUnit())
                                                <span class="badge badge-secondary ml-1" title="Unidad del sistema">
                                                    <i class="fas fa-shield-alt"></i> Sistema
                                                </span>
                                            @endif
                                        </td>
                                        <td><code class="bg-light px-2 py-1 rounded">{{ $unit->symbol }}</code></td>
                                        <td>
                                            @if ($usage['materials_count'] > 0)
                                                <span class="badge badge-info mr-1">
                                                    <i class="fas fa-boxes"></i> {{ $usage['materials_count'] }} materiales
                                                </span>
                                            @endif
                                            @if ($usage['conversions_count'] > 0)
                                                <span class="badge badge-warning mr-1">
                                                    <i class="fas fa-exchange-alt"></i> {{ $usage['conversions_count'] }} conversiones
                                                </span>
                                            @endif
                                            @if ($usage['categories_default_count'] > 0)
                                                <span class="badge badge-primary mr-1">
                                                    <i class="fas fa-folder"></i> {{ $usage['categories_default_count'] }} categorías
                                                </span>
                                            @endif
                                            @if (!$usage['in_use'] && !$unit->isSystemUnit())
                                                <span class="text-muted font-italic">Sin usar</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isLocked)
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-lock"></i> Protegida
                                                </span>
                                            @else
                                                <span class="badge badge-success">
                                                    <i class="fas fa-unlock"></i> Editable
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($unit->canBeEdited())
                                                <a href="{{ route('admin.units.edit', $unit->id) }}"
                                                    class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @else
                                                <button class="btn btn-secondary btn-sm" disabled title="No editable">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            @endif
                                            @if ($unit->canBeDeleted())
                                                <a href="{{ route('admin.units.confirm_delete', $unit->id) }}"
                                                    class="btn btn-danger btn-sm" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No hay unidades de inventario registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- SECCIÓN: UNIDADES DE COMPRA (LOGISTIC) --}}
            {{-- ================================================================ --}}
            <div class="card card-primary card-outline mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart text-primary"></i>
                        <strong>Unidades de Compra</strong>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border-left border-primary" style="border-left-width: 4px !important;">
                        <i class="fas fa-info-circle text-primary"></i>
                        <strong>Empaques en los que adquieres materiales de proveedores.</strong>
                        <br>
                        <small class="text-muted">Ejemplos: Cono, Rollo, Saco, Bolsa, Paquete</small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 25%;">Nombre</th>
                                    <th style="width: 10%;">Símbolo</th>
                                    <th style="width: 35%;">Uso actual</th>
                                    <th style="width: 15%;">Estado</th>
                                    <th style="width: 10%;" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $purchaseUnits = $units->where('unit_type', \App\Enums\UnitType::LOGISTIC);
                                    $counter = 1;
                                @endphp
                                @forelse ($purchaseUnits as $unit)
                                    @php
                                        $usage = $unit->getUsageInfo();
                                        $isLocked = $usage['in_use'];
                                    @endphp
                                    <tr>
                                        <td>{{ $counter++ }}</td>
                                        <td><strong>{{ $unit->name }}</strong></td>
                                        <td><code class="bg-light px-2 py-1 rounded">{{ $unit->symbol }}</code></td>
                                        <td>
                                            @if ($usage['materials_count'] > 0)
                                                <span class="badge badge-info mr-1">
                                                    <i class="fas fa-boxes"></i> {{ $usage['materials_count'] }} materiales
                                                </span>
                                            @endif
                                            @if ($usage['categories_count'] > 0)
                                                <span class="badge badge-primary mr-1">
                                                    <i class="fas fa-folder"></i> {{ $usage['categories_count'] }} categorías
                                                </span>
                                            @endif
                                            @if ($usage['conversions_count'] > 0)
                                                <span class="badge badge-warning mr-1">
                                                    <i class="fas fa-exchange-alt"></i> {{ $usage['conversions_count'] }} conversiones
                                                </span>
                                            @endif
                                            @if (!$usage['in_use'])
                                                <span class="text-muted font-italic">Sin usar</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isLocked)
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-lock"></i> En uso
                                                </span>
                                            @else
                                                <span class="badge badge-success">
                                                    <i class="fas fa-unlock"></i> Editable
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.units.edit', $unit->id) }}"
                                                class="btn btn-warning btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if ($unit->canBeDeleted())
                                                <a href="{{ route('admin.units.confirm_delete', $unit->id) }}"
                                                    class="btn btn-danger btn-sm" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No hay unidades de compra registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- SECCIÓN: PRESENTACIONES MÉTRICAS (METRIC_PACK) --}}
            {{-- ================================================================ --}}
            @php
                $metricPacks = $units->where('unit_type', \App\Enums\UnitType::METRIC_PACK);
            @endphp
            @if ($metricPacks->count() > 0)
                <div class="card card-warning card-outline mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-box text-warning"></i>
                            <strong>Presentaciones con Contenido Fijo</strong>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light border-left border-warning" style="border-left-width: 4px !important;">
                            <i class="fas fa-info-circle text-warning"></i>
                            <strong>Empaques comerciales con cantidad predefinida.</strong>
                            <br>
                            <small class="text-muted">Ejemplos: Rollo 25m, Caja 100pz, Galón 10L</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 20%;">Nombre</th>
                                        <th style="width: 10%;">Símbolo</th>
                                        <th style="width: 25%;">Contenido</th>
                                        <th style="width: 20%;">Uso actual</th>
                                        <th style="width: 10%;" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $counter = 1; @endphp
                                    @foreach ($metricPacks as $unit)
                                        @php
                                            $usage = $unit->getUsageInfo();
                                        @endphp
                                        <tr>
                                            <td>{{ $counter++ }}</td>
                                            <td><strong>{{ $unit->name }}</strong></td>
                                            <td><code class="bg-light px-2 py-1 rounded">{{ $unit->symbol }}</code></td>
                                            <td>
                                                @if ($unit->compatibleBaseUnit)
                                                    <span class="badge badge-success">
                                                        {{ number_format($unit->default_conversion_factor, 2) }}
                                                        {{ $unit->compatibleBaseUnit->symbol }}
                                                    </span>
                                                    <small class="text-muted">por {{ $unit->symbol }}</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($usage['conversions_count'] > 0)
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-exchange-alt"></i> {{ $usage['conversions_count'] }} conversiones
                                                    </span>
                                                @else
                                                    <span class="text-muted font-italic">Sin usar</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('admin.units.edit', $unit->id) }}"
                                                    class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if ($unit->canBeDeleted())
                                                    <a href="{{ route('admin.units.confirm_delete', $unit->id) }}"
                                                        class="btn btn-danger btn-sm" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- LEYENDA --}}
            <div class="card bg-light mt-4">
                <div class="card-body py-2">
                    <small>
                        <strong>Leyenda:</strong>
                        <span class="badge badge-success mr-2"><i class="fas fa-warehouse"></i> Inventario</span>
                        Unidad en la que se controlan existencias
                        <span class="mx-2">|</span>
                        <span class="badge badge-primary mr-2"><i class="fas fa-shopping-cart"></i> Compra</span>
                        Empaque de proveedor
                        <span class="mx-2">|</span>
                        <span class="badge badge-warning mr-2"><i class="fas fa-box"></i> Presentación</span>
                        Empaque con contenido fijo
                        <span class="mx-2">|</span>
                        <span class="badge badge-secondary mr-2"><i class="fas fa-lock"></i></span>
                        No se puede eliminar
                    </small>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .card-outline {
            border-top: 3px solid;
        }

        .card-success.card-outline {
            border-top-color: #28a745;
        }

        .card-primary.card-outline {
            border-top-color: #007bff;
        }

        .card-warning.card-outline {
            border-top-color: #ffc107;
        }

        .table-sm td,
        .table-sm th {
            padding: 0.5rem;
            vertical-align: middle;
        }

        code {
            font-size: 0.9rem;
        }
    </style>
@stop
