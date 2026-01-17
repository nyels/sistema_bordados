@extends('adminlte::page')

@section('title', 'Editar Conversión - ' . $material->name)

@section('content_header')
@stop

@section('content')
    <br>

    {{-- ERRORES DE VALIDACIÓN --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Se encontraron errores:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

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
                    <li class="breadcrumb-item active">Editar Conversión</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                <i class="fas fa-edit"></i> EDITAR CONVERSIÓN DE UNIDAD
            </h3>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.material-conversions.update', [$material->id, $conversion->id]) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="material_id" value="{{ $material->id }}">

                <div class="row">

                    {{-- COLUMNA izquierda --}}
                    <div class="col-md-6" style="padding-left: 30px;">
                        <div style="border-bottom: 3px solid #28a745; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #28a745; font-weight: 600;">
                                <i class="fas fa-calculator"></i> Vista Previa
                            </h5>
                        </div>
                        {{-- INFO DEL MATERIAL --}}
                        <div class="card bg-light mb-3">
                            <div class="card-body py-3">
                                <h6 class="mb-2"><i class="fas fa-box text-primary"></i> Material</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td style="width: 140px;"><strong>Nombre:</strong></td>
                                        <td>{{ $material->name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Categoría:</strong></td>
                                        <td>{{ $material->category->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Unidad de Compra:</strong></td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <i class="fas fa-dolly mr-1"></i>
                                                {{ $material->baseUnit->name ?? 'N/A' }}
                                                ({{ $material->baseUnit->symbol ?? '' }})
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Unidad de Consumo:</strong></td>
                                        <td>
                                            <span class="badge badge-success">
                                                <i class="fas fa-ruler mr-1"></i>
                                                {{ $consumptionUnit->name ?? 'N/A' }}
                                                ({{ $consumptionUnit->symbol ?? '' }})
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>



                        {{-- INFO --}}
                        <div class="card bg-light mt-3">
                            <div class="card-body py-2">
                                <small>
                                    <strong>Creado:</strong> {{ $conversion->created_at->format('d/m/Y H:i') }}<br>
                                    <strong>Actualizado:</strong> {{ $conversion->updated_at->format('d/m/Y H:i') }}
                                </small>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Advertencia:</strong> Modificar el factor de conversión no afecta compras anteriores,
                            solo las nuevas compras usarán el nuevo factor.
                        </div>
                    </div>

                    {{-- COLUMNA derecha --}}
                    <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 30px;">
                        <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #856404; font-weight: 600;">
                                <i class="fas fa-exchange-alt"></i> Configurar Conversión
                            </h5>
                        </div>

                        <div class="form-group">
                            <label>Unidad Alternativa de Compra <span class="text-danger">*</span></label>
                            <select name="from_unit_id" id="from_unit_id"
                                class="form-control @error('from_unit_id') is-invalid @enderror" required>
                                <option value="">Seleccionar unidad...</option>
                                @foreach ($purchaseUnits as $unit)
                                    @if (!in_array($unit->id, $usedUnitIds) || $unit->id == $conversion->from_unit_id)
                                        <option value="{{ $unit->id }}"
                                            data-symbol="{{ $unit->symbol }}"
                                            data-factor="{{ $unit->default_conversion_factor }}"
                                            {{ old('from_unit_id', $conversion->from_unit_id) == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->name }} ({{ $unit->symbol }})
                                            @if($unit->isMetricPack() && $unit->default_conversion_factor)
                                                = {{ number_format($unit->default_conversion_factor, 0) }} {{ $consumptionUnit->symbol ?? '' }}
                                            @endif
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('from_unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Unidades compatibles con
                                <strong>{{ $consumptionUnit->name ?? 'N/A' }}</strong>
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Unidad de Consumo (Destino)</label>
                            <input type="hidden" name="to_unit_id" value="{{ $consumptionUnit->id ?? '' }}">
                            <div class="form-control bg-light" style="cursor: not-allowed;">
                                <span class="badge badge-success">
                                    <i class="fas fa-ruler mr-1"></i>
                                    {{ $consumptionUnit->name ?? 'N/A' }}
                                    ({{ $consumptionUnit->symbol ?? '' }})
                                </span>
                                <i class="fas fa-lock text-muted float-right mt-1"></i>
                            </div>
                            <small class="form-text text-muted">
                                Unidad de consumo del material. No editable.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Factor de Conversión <span class="text-danger">*</span></label>
                            <input type="number" name="conversion_factor" id="conversion_factor"
                                class="form-control @error('conversion_factor') is-invalid @enderror"
                                value="{{ old('conversion_factor', $conversion->conversion_factor) }}" min="0.0001"
                                max="999999999" step="0.0001" required>
                            @error('conversion_factor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                ¿Cuántas <strong>{{ $consumptionUnit->symbol ?? 'unidades' }}</strong> hay en
                                1 unidad de compra?
                            </small>
                        </div>

                        {{-- PREVIEW DE CONVERSIÓN --}}
                        <div class="card border-primary" id="previewCard" style="display: none;">
                            <div class="card-header bg-primary text-white py-2">
                                <strong><i class="fas fa-eye"></i> Resultado de Conversión</strong>
                            </div>
                            <div class="card-body text-center py-4">
                                <h4 class="mb-0">
                                    <span class="badge badge-secondary" id="previewFrom">1 ?</span>
                                    <i class="fas fa-arrow-right mx-2 text-primary"></i>
                                    <span class="badge badge-success" id="previewTo">? unidades</span>
                                </h4>
                                <hr>
                                <p class="mb-0 text-muted">
                                    <small id="previewText">-</small>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <hr>

                <div class="text-center">
                    <a href="{{ route('admin.material-conversions.index', $material->id) }}" class="btn btn-secondary">
                        <i class="fas fa-times-circle"></i> Regresar
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function() {
            // Datos de la unidad de consumo (destino) desde el servidor
            var toUnitName = '{{ $consumptionUnit->name ?? 'N/A' }}';
            var toUnitSymbol = '{{ $consumptionUnit->symbol ?? '' }}';

            function updatePreview() {
                var fromUnit = $('#from_unit_id option:selected');
                var factor = parseFloat($('#conversion_factor').val()) || 0;

                if (fromUnit.val() && factor > 0) {
                    var fromSymbol = fromUnit.data('symbol') || fromUnit.text();
                    var fromName = fromUnit.text().split('(')[0].trim();

                    $('#previewFrom').text('1 ' + fromSymbol);
                    $('#previewTo').text(factor.toFixed(2) + ' ' + toUnitSymbol);
                    $('#previewText').text('Al comprar 1 ' + fromName +
                        ', se registrarán ' + factor.toFixed(2) + ' ' + toUnitSymbol + ' en inventario');
                    $('#previewCard').show();
                } else {
                    $('#previewCard').hide();
                }
            }

            $('#from_unit_id, #conversion_factor').on('change keyup', updatePreview);

            // Trigger on load para mostrar preview inicial
            updatePreview();
        });
    </script>
@stop
