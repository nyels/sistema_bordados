@extends('adminlte::page')

@section('title', 'Editar Extra')

@section('content_header')
@stop

@section('content')
    <br>

    {{-- MENSAJES FLASH --}}
    @foreach (['success', 'error', 'info'] as $msg)
        @if (session($msg))
            <div class="alert alert-{{ $msg == 'error' ? 'danger' : $msg }} alert-dismissible fade show" role="alert">
                {{ session($msg) }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif
    @endforeach

    {{-- ERRORES GENERALES --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Se encontraron errores en el formulario:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title" style="font-weight: bold; font-size: 20px;"> EDITAR EXTRA DE PRODUCTO</h3>
        </div>

        <div class="card-body">
            <form method="post" action="{{ route('admin.product_extras.update', $extra->id) }}" id="formExtra">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-8 offset-md-2">

                        <div style="border-bottom: 3px solid #ffc107; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #ffc107; font-weight: 600;">
                                <i class="fas fa-edit"></i> Datos del Extra
                            </h5>
                        </div>

                        {{-- Nombre --}}
                        <div class="form-group">
                            <label>Nombre del Extra <span style="color: red;">*</span></label>
                            <input type="text" name="name"
                                class="form-control form-control-sm @error('name') is-invalid @enderror"
                                value="{{ old('name', $extra->name) }}" required
                                placeholder="Ej: Empaque especial, Urgencia, etc.">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Costo y Precio --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Costo Adicional <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" name="cost_addition"
                                            class="form-control form-control-sm @error('cost_addition') is-invalid @enderror"
                                            value="{{ old('cost_addition', $extra->cost_addition) }}" required
                                            step="0.01" min="0" placeholder="0.00">
                                        @error('cost_addition')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted">Costo real del servicio/extra</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Precio al Cliente <span style="color: red;">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" name="price_addition"
                                            class="form-control form-control-sm @error('price_addition') is-invalid @enderror"
                                            value="{{ old('price_addition', $extra->price_addition) }}" required
                                            step="0.01" min="0" placeholder="0.00">
                                        @error('price_addition')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted">Precio que se cobra al cliente</small>
                                </div>
                            </div>
                        </div>

                        {{-- Tiempo adicional --}}
                        <div class="form-group">
                            <label>Tiempo Adicional (minutos)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="minutes_addition"
                                    class="form-control form-control-sm @error('minutes_addition') is-invalid @enderror"
                                    value="{{ old('minutes_addition', $extra->minutes_addition) }}" min="0"
                                    max="9999" step="1"
                                    onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="0">
                                <div class="input-group-append">
                                    <span class="input-group-text">minutos</span>
                                </div>
                                @error('minutes_addition')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Tiempo extra que agrega este servicio al proceso</small>
                        </div>

                        {{-- Botones --}}
                        <div class="text-center mt-4">
                            <a href="{{ route('admin.product_extras.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times-circle"></i> Regresar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#formExtra').on('submit', function(e) {
                var name = $('input[name="name"]').val().trim();
                var cost = $('input[name="cost_addition"]').val();
                var price = $('input[name="price_addition"]').val();

                var errors = [];

                if (!name || name === '') {
                    errors.push('El nombre del extra es obligatorio');
                }
                if (!cost || cost === '' || parseFloat(cost) < 0) {
                    errors.push('El costo adicional es obligatorio');
                }
                if (!price || price === '' || parseFloat(price) < 0) {
                    errors.push('El precio al cliente es obligatorio');
                }

                if (errors.length > 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Campos requeridos',
                        html: '<ul style="text-align:left;">' + errors.map(function(err) {
                            return '<li>' + err + '</li>';
                        }).join('') + '</ul>',
                        confirmButtonText: 'Entendido'
                    });
                    return false;
                }

                return true;
            });
        });
    </script>
@stop
