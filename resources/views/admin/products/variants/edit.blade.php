@extends('adminlte::page')

@section('title', 'Editar Variante - ' . $variant->sku_variant)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-edit mr-2"></i> Editar Variante</h1>
            <p class="text-muted mb-0">
                Producto: <strong>{{ $product->name }}</strong> ({{ $product->sku }})
            </p>
        </div>
        <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Producto
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">
                        <i class="fas fa-layer-group mr-2"></i>
                        Variante: {{ $variant->attributes_display ?: $variant->sku_variant }}
                    </h3>
                </div>
                <form action="{{ route('admin.products.variants.update', ['product' => $product->id, 'variant' => $variant->id]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sku_variant" class="font-weight-bold">
                                        <i class="fas fa-barcode mr-1"></i> SKU Variante *
                                    </label>
                                    <input type="text"
                                           class="form-control text-uppercase @error('sku_variant') is-invalid @enderror"
                                           id="sku_variant"
                                           name="sku_variant"
                                           value="{{ old('sku_variant', $variant->sku_variant) }}"
                                           required>
                                    @error('sku_variant')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Identificador unico de esta variante</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price" class="font-weight-bold">
                                        <i class="fas fa-dollar-sign mr-1"></i> Precio Override *
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number"
                                               class="form-control @error('price') is-invalid @enderror"
                                               id="price"
                                               name="price"
                                               value="{{ old('price', $variant->price) }}"
                                               step="0.01"
                                               min="0"
                                               required>
                                        @error('price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted">
                                        Precio base del producto: <strong>${{ number_format($product->base_price, 2) }}</strong>
                                        <br>Deja este valor si no hay diferencia de precio.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="stock_alert" class="font-weight-bold">
                                        <i class="fas fa-bell mr-1"></i> Alerta de Stock
                                    </label>
                                    <input type="number"
                                           class="form-control @error('stock_alert') is-invalid @enderror"
                                           id="stock_alert"
                                           name="stock_alert"
                                           value="{{ old('stock_alert', $variant->stock_alert) }}"
                                           min="0">
                                    @error('stock_alert')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle text-info"></i>
                                        Solo genera alertas visuales. <strong>NO bloquea</strong> ventas ni producción.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-tags mr-1"></i> Atributos Actuales
                                    </label>
                                    <div class="form-control-plaintext">
                                        @forelse($variant->attributeValues as $attrVal)
                                            <span class="badge badge-info mr-1">
                                                {{ $attrVal->attribute->name ?? 'Attr' }}: {{ $attrVal->value }}
                                            </span>
                                        @empty
                                            <span class="text-muted">Sin atributos asignados</span>
                                        @endforelse
                                    </div>
                                    <small class="text-muted">Para cambiar atributos, edita el producto completo</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar Cambios
                        </button>
                        <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-secondary">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i> Informacion</h3>
                </div>
                <div class="card-body">
                    <dl>
                        <dt>ID Variante</dt>
                        <dd>{{ $variant->id }}</dd>
                        <dt>UUID</dt>
                        <dd><small class="text-monospace">{{ $variant->uuid }}</small></dd>
                        <dt>Creada</dt>
                        <dd>{{ $variant->created_at->format('d/m/Y H:i') }}</dd>
                        <dt>Actualizada</dt>
                        <dd>{{ $variant->updated_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@stop
