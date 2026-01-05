{{-- resources/views/admin/products/show.blade.php --}}
@extends('adminlte::page')

@section('title', 'Detalle del Producto')

@section('content_header')
@stop

@section('content')
    <br>
    <div class="card card-primary" bis_skin_checked="1">
        <div class="card-header" bis_skin_checked="1">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title" style="font-weight: bold;font-size: 20px;">
                    DETALLE DEL PRODUCTO: {{ $product->name }}
                </h3>
                <div class="btn-group">
                    <a href="{{ route('products.index') }}" class="btn btn-default btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </a>
                    <a href="{{ route('products.edit', $product->id) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit mr-1"></i> Editar
                    </a>
                    @if ($product->canDelete())
                        <a href="{{ route('products.confirm_delete', $product->id) }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash mr-1"></i> Eliminar
                        </a>
                    @endif
                    @if ($product->status === 'active')
                        <form action="{{ route('products.toggle_status', $product->id) }}" method="POST" class="d-inline"
                            data-confirm="¿Cambiar a descontinuado?">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <i class="fas fa-ban mr-1"></i> Descontinuar
                            </button>
                        </form>
                    @elseif($product->status === 'discontinued')
                        <form action="{{ route('products.toggle_status', $product->id) }}" method="POST" class="d-inline"
                            data-confirm="¿Activar producto?">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-check mr-1"></i> Activar
                            </button>
                        </form>
                    @endif
                    <form action="{{ route('products.duplicate', $product->id) }}" method="POST" class="d-inline"
                        data-confirm="¿Duplicar este producto?">
                        @csrf
                        <button type="submit" class="btn btn-info btn-sm">
                            <i class="fas fa-copy mr-1"></i> Duplicar
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- /.card-header -->

        <div class="card-body" bis_skin_checked="1">
            <!-- Mensajes de éxito/error -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <!-- Columna izquierda: Información principal -->
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info"><i class="fas fa-barcode"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">SKU</span>
                                    <span class="info-box-number">{{ $product->sku }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-{{ $product->status_color }}">
                                    <i
                                        class="fas fa-{{ $product->status === 'active' ? 'check' : ($product->status === 'draft' ? 'file' : 'ban') }}"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Estado</span>
                                    <span class="info-box-number">{{ $product->status_label }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-success"><i class="fas fa-tags"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Categoría</span>
                                    <span class="info-box-number">{{ $product->category->name ?? 'Sin categoría' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-warning"><i class="fas fa-dollar-sign"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Precio Base</span>
                                    <span class="info-box-number">{{ $product->formatted_base_price }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="card card-outline card-info mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Descripción</h3>
                        </div>
                        <div class="card-body">
                            {{ $product->description ?? 'Sin descripción' }}
                        </div>
                    </div>

                    <!-- Especificaciones -->
                    @if ($product->specifications && count($product->specifications) > 0)
                        <div class="card card-outline card-success mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Especificaciones Técnicas</h3>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width: 40%">Característica</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($product->specifications as $key => $value)
                                            <tr>
                                                <td><strong>{{ $key }}</strong></td>
                                                <td>{{ $value }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Columna derecha: Imágenes y extras -->
                <div class="col-md-4">
                    <!-- Imagen principal -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Imagen Principal</h3>
                        </div>
                        <div class="card-body text-center">
                            @if ($product->primary_image_url)
                                <img src="{{ asset($product->primary_image_url) }}" alt="{{ $product->name }}"
                                    class="img-fluid" style="max-height: 250px; object-fit: contain;">
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-image fa-5x text-muted"></i>
                                    <p class="mt-2 text-muted">Sin imagen</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Extras -->
                    @if ($product->extras->count() > 0)
                        <div class="card card-outline card-warning mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Extras Asociados</h3>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    @foreach ($product->extras as $extra)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $extra->name }}
                                            <span
                                                class="badge badge-primary badge-pill">{{ $extra->formatted_price }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- Estadísticas -->
                    <div class="card card-outline card-secondary mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Estadísticas</h3>
                        </div>
                        <div class="card-body">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $product->variants_count }}</h3>
                                    <p>Variantes</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <a href="#variants-section" class="small-box-footer">
                                    Ver variantes <i class="fas fa-arrow-circle-down"></i>
                                </a>
                            </div>

                            @if ($product->designs->count() > 0)
                                <div class="small-box bg-success mt-3">
                                    <div class="inner">
                                        <h3>{{ $product->designs->count() }}</h3>
                                        <p>Diseños Asociados</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-palette"></i>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diseños Asociados -->
            @if ($product->designs->count() > 0)
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h3 class="card-title">Diseños Asociados</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach ($product->designs as $design)
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h5 class="card-title">{{ $design->name }}</h5>
                                                    <p class="card-text">
                                                        <strong>Código:</strong> {{ $design->code }}<br>
                                                        <strong>Puntadas:</strong> {{ $design->stitch_count ?? 'N/A' }}<br>
                                                        <strong>Tipo de Aplicación:</strong>
                                                        {{ $design->pivot->application_type_id ? $applicationTypes->firstWhere('id', $design->pivot->application_type_id)->nombre ?? 'N/A' : 'N/A' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Variantes del Producto -->
            <div class="row mt-4" id="variants-section">
                <div class="col-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Variantes del Producto</h3>
                            <a href="{{ route('products.variants.create', $product->id) }}"
                                class="btn btn-success btn-sm">
                                <i class="fas fa-plus mr-1"></i> Nueva Variante
                            </a>
                        </div>
                        <div class="card-body">
                            @if ($product->variants->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>SKU Variante</th>
                                                <th>Atributos</th>
                                                <th>Precio</th>
                                                <th>Precio + Extras</th>
                                                <th>Alerta Stock</th>
                                                <th>Exportaciones</th>
                                                <th style="width: 120px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($product->variants as $variant)
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-dark">{{ $variant->sku_variant }}</span>
                                                    </td>
                                                    <td>
                                                        @if ($variant->attributes->count() > 0)
                                                            <div class="d-flex flex-wrap gap-1">
                                                                @foreach ($variant->attributes as $attribute)
                                                                    <span class="badge badge-info">
                                                                        {{ $attribute->attribute->name ?? 'N/A' }}:
                                                                        {{ $attribute->value }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="text-muted">Sin atributos</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-right font-weight-bold">
                                                        {{ $variant->formatted_price }}</td>
                                                    <td class="text-right font-weight-bold text-success">
                                                        {{ $variant->formatted_total_with_extras }}</td>
                                                    <td class="text-center">
                                                        <span
                                                            class="badge badge-{{ $variant->stock_alert > 0 ? 'warning' : 'secondary' }}">
                                                            {{ $variant->stock_alert }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span
                                                            class="badge badge-secondary">{{ $variant->designExports->count() }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="#" class="btn btn-info" title="Ver">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="{{ route('products.variants.edit', ['productId' => $product->id, 'variantId' => $variant->id]) }}"
                                                                class="btn btn-warning" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form
                                                                action="{{ route('products.variants.destroy', ['productId' => $product->id, 'variantId' => $variant->id]) }}"
                                                                method="POST" class="d-inline"
                                                                data-confirm="¿Eliminar esta variante?">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger"
                                                                    title="Eliminar">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No hay variantes registradas</h5>
                                    <p class="text-muted">Crea la primera variante para este producto</p>
                                    <a href="{{ route('products.variants.create', $product->id) }}"
                                        class="btn btn-primary">
                                        <i class="fas fa-plus mr-1"></i> Crear Primera Variante
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de Auditoría -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Información de Auditoría</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <p><strong>Creado:</strong><br>
                                        {{ $product->created_at->format('d/m/Y H:i:s') }}<br>
                                        <small class="text-muted">{{ $product->created_at->diffForHumans() }}</small>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Actualizado:</strong><br>
                                        {{ $product->updated_at->format('d/m/Y H:i:s') }}<br>
                                        <small class="text-muted">{{ $product->updated_at->diffForHumans() }}</small>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>UUID:</strong><br>
                                        <code>{{ $product->uuid }}</code>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Tenant ID:</strong><br>
                                        {{ $product->tenant_id }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card-body -->

        <div class="card-footer" bis_skin_checked="1">
            <div class="row">
                <div class="col-md-6">
                    <a href="{{ route('products.index') }}" class="btn btn-default">
                        <i class="fas fa-arrow-left mr-1"></i> Volver al Listado
                    </a>
                </div>
                <div class="col-md-6 text-right">
                    <div class="btn-group">
                        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit mr-1"></i> Editar Producto
                        </a>
                        @if ($product->canDelete())
                            <a href="{{ route('products.confirm_delete', $product->id) }}" class="btn btn-danger">
                                <i class="fas fa-trash mr-1"></i> Eliminar Producto
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .info-box {
            margin-bottom: 15px;
            box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
            border-radius: .25rem;
        }

        .small-box {
            border-radius: .25rem;
            position: relative;
            display: block;
            margin-bottom: 20px;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        }

        .small-box>.inner {
            padding: 10px;
        }

        .small-box .icon {
            position: absolute;
            top: -10px;
            right: 10px;
            z-index: 0;
            font-size: 70px;
            color: rgba(0, 0, 0, 0.15);
        }

        .gap-1 {
            gap: 0.25rem;
        }
    </style>
@stop

@section('js')
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // SweetAlert para confirmaciones
            $(document).on('submit', 'form[data-confirm]', function(e) {
                e.preventDefault();
                var form = this;

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: $(this).data('confirm'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Scroll suave a la sección de variantes
            $('a[href="#variants-section"]').click(function(e) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $("#variants-section").offset().top - 20
                }, 500);
            });

            // Auto-ocultar alertas después de 5 segundos
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
@stop
