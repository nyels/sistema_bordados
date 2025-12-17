@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary" bis_skin_checked="1">

        <div class="card-header" bis_skin_checked="1">

            <h3 class="card-title" style="font-weight: bold;font-size: 20px;"> NUEVO PROVEEDOR</h3>
            <!-- /.card-tools -->
        </div>
        <!-- /.card-header -->
        <div class="card-body" bis_skin_checked="1">
            <form method="post" action="{{ route('admin.proveedores.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6" style="border-right: 2px solid #e0e0e0; padding-right: 30px;">
                        <div style="border-bottom: 3px solid #007bff; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #007bff; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-building" style="margin-right: 10px;"></i>
                                Datos del Proveedor
                            </h5>
                        </div>
                        <div class="form-group">
                            <label for="nombre_proveedor">Nombre Proveedor <span style="color: red;">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="nombre_proveedor"
                                name="nombre_proveedor" placeholder="Ej: Textiles del Norte S.A." required>
                        </div>
                        <div class="form-group">
                            <label for="giro_id">Giro<span style="color: red;">*</span></label>
                            <select name="giro_id" id="giro_id" class="form-control form-control-sm" required>
                                <option value="">Selecciona un Giro</option>
                                <option value="1">TELAS</option>
                                <option value="2">ACCESORIOS</option>
                                <option value="3">TELAS Y ACCESORIOS</option>
                            </select>
                        </div>

                        <!-- Dirección y Código Postal en la misma fila -->
                        <div class="row">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <label for="direccion">Dirección</label>
                                    <input type="text" class="form-control form-control-sm" id="direccion"
                                        name="direccion" placeholder="Ej: Av. Juárez #123, Col. Centro">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="codigo_postal">C.P.</label>
                                    <input type="text" class="form-control form-control-sm" id="codigo_postal"
                                        name="codigo_postal" pattern="[0-9]{5}" maxlength="5" placeholder="92000"
                                        title="Ingrese un código postal de 5 dígitos">
                                </div>
                            </div>
                        </div>

                        <!-- Teléfono y Correo en la misma fila -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="telefono">Teléfono <span style="color: red;">*</span></label>
                                    <input type="tel" class="form-control form-control-sm" id="telefono"
                                        name="telefono" placeholder="Ej: 2291234567" pattern="[0-9]{10}" maxlength="10"
                                        title="Ingrese un teléfono de 10 dígitos" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="correo">Correo</label>
                                    <input type="email" class="form-control form-control-sm" id="correo" name="correo"
                                        placeholder="Ej: proveedor@ejemplo.com">
                                </div>
                            </div>
                        </div>

                        <!-- Estado y Ciudad en la misma fila -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="estado_id">Estado <span style="color: red;">*</span></label>
                                    <select name="estado_id" id="estado_id" class="form-control form-control-sm" required>
                                        <option value="">Selecciona un estado</option>
                                        @foreach ($estados as $estado)
                                            <option value="{{ $estado->id }}">{{ $estado->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ciudad">Ciudad </label>
                                    <input type="text" class="form-control form-control-sm" id="ciudad" name="ciudad"
                                        placeholder="Ej: Veracruz">
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="text-right">
                                    <a href="{{ route('admin.proveedores.index') }}" class="btn btn-secondary"
                                        style="margin-right: 10px; padding: 8px 20px;">
                                        <i class="fas fa-times-circle"></i> Regresar
                                    </a>
                                    <button type="submit" class="btn btn-primary" style="padding: 8px 20px;">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!--EXTRAS-->
                    <div class="col-md-6" style="padding-left: 30px;">
                        <div style="border-bottom: 3px solid #28a745; padding-bottom: 8px; margin-bottom: 20px;">
                            <h5 style="color: #28a745; font-weight: 600; margin: 0; display: flex; align-items: center;">
                                <i class="fas fa-address-book" style="margin-right: 10px;"></i>
                                Datos de Contacto
                            </h5>
                        </div>
                        <div class="form-group">
                            <label for="nombre_contacto">Nombre del contacto</label>
                            <input type="text" class="form-control form-control-sm" id="nombre_contacto"
                                name="nombre_contacto" placeholder="Ej: Juan Pérez">
                        </div>

                        <!-- Teléfono y Correo del contacto en la misma fila -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="telefono_contacto">Teléfono</label>
                                    <input type="tel" class="form-control form-control-sm" id="telefono_contacto"
                                        name="telefono_contacto" placeholder="Ej: 2291234567" pattern="[0-9]{10}"
                                        maxlength="10" title="Ingrese un teléfono de 10 dígitos">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="correo_contacto">Correo</label>
                                    <input type="email" class="form-control form-control-sm" id="correo_contacto"
                                        name="correo_contacto" placeholder="Ej: contacto@ejemplo.com">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </div>
        <!-- /.card-body -->
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script>
        console.log("Hi, I'm using the Laravel-AdminLTE package!");
    </script>
@stop
