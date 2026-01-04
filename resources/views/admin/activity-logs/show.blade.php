@extends('adminlte::page')

@section('title', 'Auditoría Técnica')

@section('content')
    <div class="container-fluid pt-4">
        <div class="card card-outline card-info shadow">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-fingerprint mr-2 text-info"></i> Detalle de Auditoría
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light border px-3 py-2">UUID: {{ $log->uuid }}</span>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    {{-- Bloque Izquierdo: Información del Evento --}}
                    <div class="col-md-6">
                        <h5 class="text-info border-bottom pb-2 mb-3">
                            <i class="fas fa-history mr-1"></i> Contexto del Evento
                        </h5>
                        <table class="table table-sm table-hover">
                            <tbody>
                                <tr>
                                    <th style="width: 30%">Fecha y Hora</th>
                                    <td>{{ $log->created_at->format('d/m/Y H:i:s') }}
                                        <small class="text-muted">({{ $log->created_at->diffForHumans() }})</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Operador</th>
                                    <td>
                                        <span
                                            class="badge badge-secondary px-2">{{ $log->user_name ?? 'Sistema / Cron' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Acción</th>
                                    <td>
                                        <span class="badge border text-uppercase px-2">
                                            <i class="{{ $log->action_icon }} mr-1"></i> {{ $log->action_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Módulo / Entidad</th>
                                    <td><code class="text-primary font-weight-bold">{{ $log->short_model_type }}</code></td>
                                </tr>
                                <tr>
                                    <th>ID del Registro</th>
                                    <td><span class="badge badge-light border">#{{ $log->model_id ?? 'N/A' }}</span></td>
                                </tr>
                                <tr>
                                    <th>Descripción</th>
                                    <td class="text-wrap"><strong>{{ $log->description }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Bloque Derecho: Información de Red/Técnica --}}
                    <div class="col-md-6">
                        <h5 class="text-success border-bottom pb-2 mb-3">
                            <i class="fas fa-network-wired mr-1"></i> Trazabilidad Técnica
                        </h5>
                        <table class="table table-sm table-hover">
                            <tbody>
                                <tr>
                                    <th style="width: 30%">Dirección IP</th>
                                    <td><code class="bg-light px-2 rounded">{{ $log->ip_address ?? 'Local/Unknown' }}</code>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Método HTTP</th>
                                    <td><span class="badge badge-outline-dark">{{ $log->method ?? 'N/A' }}</span></td>
                                </tr>
                                <tr>
                                    <th>URL Solicitada</th>
                                    <td><small class="text-break">{{ $log->url ?? 'N/A' }}</small></td>
                                </tr>
                                <tr>
                                    <th>Agente de Navegación</th>
                                    <td><small class="text-muted text-break">{{ $log->user_agent ?? 'N/A' }}</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Comparativa de Valores (Solo si hay cambios) --}}
                @if ($log->action === 'updated' && $log->new_values)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-warning border-bottom pb-2 mb-3">
                                <i class="fas fa-columns mr-1"></i> Comparativa de Cambios
                            </h5>
                            <div class="table-responsive border rounded">
                                <table class="table table-striped table-valign-middle m-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Atributo Modificado</th>
                                            <th class="text-danger">Valor Anterior</th>
                                            <th class="text-success">Valor Nuevo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($log->new_values as $key => $value)
                                            <tr>
                                                <td class="font-weight-bold text-muted">
                                                    {{ str($key)->replace('_', ' ')->title() }}</td>
                                                <td class="bg-soft-danger">
                                                    @if (isset($log->old_values[$key]))
                                                        <del>{{ is_array($log->old_values[$key]) ? json_encode($log->old_values[$key]) : $log->old_values[$key] }}</del>
                                                    @else
                                                        <span class="text-muted small"><em>Nulo</em></span>
                                                    @endif
                                                </td>
                                                <td class="bg-soft-success font-weight-bold">
                                                    {{ is_array($value) ? json_encode($value) : $value }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @elseif($log->action === 'created' || $log->action === 'deleted')
                    {{-- Para creación o eliminación mostramos el bloque de datos completo --}}
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-database mr-1"></i> Datos del Registro ({{ $log->action_label }})
                            </h5>
                            <div class="bg-light p-3 border rounded">
                                <pre class="m-0"><code>{{ json_encode($log->action === 'created' ? $log->new_values : $log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="card-footer bg-white text-right">
                <a href="{{ route('activity-logs.show', $log->uuid) }}" class="btn btn-sm btn-info">
                    <i class="fas fa-eye"></i>
                </a>
            </div>
        </div>
    </div>
@stop

@push('css')
    <style>
        .bg-soft-danger {
            background-color: rgba(220, 53, 69, 0.05);
        }

        .bg-soft-success {
            background-color: rgba(40, 167, 69, 0.05);
        }

        pre {
            font-size: 85%;
        }
    </style>
@endpush
