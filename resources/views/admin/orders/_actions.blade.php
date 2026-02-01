@php
    use App\Models\Order;
    $hasAdjustments = $order->hasPendingAdjustments();
    $hasDesignPending = $order->hasItemsPendingDesignApproval();
    $hasMeasurementsChanged = $order->items->contains(fn($i) => $i->hasMeasurementsChangedAfterApproval());
    $hasMissingTechnicalDesigns = $order->hasItemsMissingTechnicalDesigns();
    $hasBlockers = $hasAdjustments || $hasDesignPending || $hasMeasurementsChanged || $hasMissingTechnicalDesigns;
    $canStartProduction = $order->status === Order::STATUS_CONFIRMED && !$hasBlockers;
@endphp

@if(!in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_DELIVERED]))
    <div class="card">
        <div class="card-header py-2" style="background: #343a40; color: white;">
            <h5 class="mb-0"><i class="fas fa-bolt mr-2"></i> Acciones</h5>
        </div>
        <div class="card-body">

            {{-- ================================================================ --}}
            {{-- DRAFT: Puede confirmar --}}
            {{-- ================================================================ --}}
            @if($order->status === Order::STATUS_DRAFT)
                <div class="alert alert-info py-2 mb-3" style="font-size: 14px;">
                    <i class="fas fa-edit mr-1"></i>
                    <strong>Borrador:</strong> Puede editar libremente productos, cantidades y precios.
                </div>
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="confirmed">
                    <button type="submit" class="btn btn-info btn-block">
                        <i class="fas fa-check mr-1"></i> Confirmar Pedido
                    </button>
                </form>
                <span style="font-size: 14px; color: #495057;" class="d-block text-center">
                    Al confirmar, el pedido queda listo para revisión de diseño y producción.
                </span>
            @endif

            {{-- ================================================================ --}}
            {{-- CONFIRMED: Puede iniciar producción (con validaciones) --}}
            {{-- ================================================================ --}}
            @if($order->status === Order::STATUS_CONFIRMED)
                {{-- Advertencia de congelamiento --}}
                <div class="mb-3 p-2 rounded" style="background: #fff3cd; border: 1px solid #ffc107;">
                    <div style="font-size: 14px; color: #856404;">
                        <i class="fas fa-lock mr-1"></i> <strong>Al iniciar producción:</strong>
                    </div>
                    <ul class="mb-0 pl-3 mt-1" style="font-size: 14px; color: #856404;">
                        <li>El pedido queda <strong>CONGELADO</strong></li>
                        <li>Productos, medidas y diseños se sellan</li>
                        <li>Materiales se reservan del inventario</li>
                        <li><strong>NO</strong> se permiten cambios posteriores</li>
                    </ul>
                </div>

                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="in_production">
                    <button type="submit" class="btn btn-warning btn-block" {{ $canStartProduction ? '' : 'disabled' }}
                            title="Esta acción sella el pedido para producción">
                        <i class="fas fa-cogs mr-1"></i> Iniciar Producción
                    </button>
                </form>

                @if($canStartProduction)
                    <span style="font-size: 14px; color: #28a745;" class="d-block text-center mb-2">
                        <i class="fas fa-check-circle mr-1"></i> Pedido listo para producción
                    </span>
                @else
                    <span style="font-size: 14px; color: #c62828;" class="d-block text-center">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Resuelva los bloqueos primero (ver abajo)
                    </span>
                @endif
            @endif

            {{-- ================================================================ --}}
            {{-- IN_PRODUCTION: Puede marcar listo (con modal de productos) --}}
            {{-- ================================================================ --}}
            @if($order->status === Order::STATUS_IN_PRODUCTION)
                <div class="alert alert-warning py-2 mb-3" style="font-size: 14px;">
                    <i class="fas fa-industry mr-1"></i>
                    <strong>En Producción:</strong>
                    <ul class="mb-0 pl-3 mt-1">
                        <li>Pedido congelado - sin modificaciones</li>
                        <li>Materiales reservados en inventario</li>
                        <li><strong>NO</strong> se permiten anexos ni extras</li>
                    </ul>
                </div>

                {{-- Botón que abre el modal de productos --}}
                <button type="button" class="btn btn-success btn-block mb-2" data-toggle="modal" data-target="#modalProductosListo">
                    <i class="fas fa-box mr-1"></i> Marcar Listo
                </button>
                <span style="font-size: 14px; color: #495057;" class="d-block text-center">
                    <i class="fas fa-info-circle mr-1"></i> Marque cada producto como terminado
                </span>
            @endif

            {{-- ================================================================ --}}
            {{-- READY: Cierre según tipo de pedido --}}
            {{-- ================================================================ --}}
            @if($order->status === Order::STATUS_READY)
                @if($order->isStockProduction())
                    {{-- STOCK_PRODUCTION: Entrada a inventario de producto terminado --}}
                    <div class="alert alert-success py-2 mb-3" style="font-size: 14px;">
                        <i class="fas fa-check-circle mr-1"></i>
                        <strong>Listo para Inventario:</strong>
                        <ul class="mb-0 pl-3 mt-1">
                            <li>Producción completada</li>
                            <li>Materiales consumidos</li>
                            <li>Producto terminado disponible</li>
                        </ul>
                    </div>

                    <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="delivered">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-warehouse mr-1"></i> Ingresar a Inventario
                        </button>
                    </form>
                    <span style="font-size: 14px; color: #495057;" class="d-block text-center">
                        <i class="fas fa-info-circle mr-1"></i> Registra productos terminados en stock
                    </span>
                @else
                    {{-- SALE: Entrega a cliente --}}
                    <div class="alert alert-success py-2 mb-3" style="font-size: 14px;">
                        <i class="fas fa-check-circle mr-1"></i>
                        <strong>Listo para Entrega:</strong>
                        <ul class="mb-0 pl-3 mt-1">
                            <li>Producción completada</li>
                            <li>Materiales consumidos</li>
                            <li>NO se permiten anexos</li>
                        </ul>
                    </div>

                    <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="mb-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="delivered">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-truck mr-1"></i> Registrar Entrega
                        </button>
                    </form>
                    <span style="font-size: 14px; color: #495057;" class="d-block text-center">
                        <i class="fas fa-info-circle mr-1"></i> Cierra el ciclo del pedido
                    </span>
                @endif
            @endif

        </div>
    </div>
@endif

{{-- ================================================================ --}}
{{-- MODAL: Productos del Pedido para marcar como Terminados --}}
{{-- ================================================================ --}}
@if($order->status === Order::STATUS_IN_PRODUCTION)
<div class="modal fade" id="modalProductosListo" tabindex="-1" aria-labelledby="modalProductosListoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalProductosListoLabel">
                    <i class="fas fa-tasks mr-2"></i>Productos del Pedido
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 mb-3" style="font-size: 14px;">
                    <i class="fas fa-info-circle mr-1"></i>
                    Marque cada producto como <strong>Terminado</strong> conforme se complete su producción.
                    Cuando todos estén listos, podrá finalizar el pedido.
                </div>

                {{-- Barra de progreso --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size: 14px; font-weight: 600;">Progreso de Producción</span>
                        <span id="progressLabel" style="font-size: 14px; font-weight: 600;">0 / {{ $order->items->count() }}</span>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div id="progressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                {{-- Lista de productos --}}
                <div class="list-group" id="listaProductos">
                    @foreach($order->items as $item)
                        @php
                            $isCompleted = $item->production_completed ?? false;
                        @endphp
                        <div class="list-group-item d-flex justify-content-between align-items-center producto-item {{ $isCompleted ? 'bg-light' : '' }}"
                             data-item-id="{{ $item->id }}"
                             data-completed="{{ $isCompleted ? '1' : '0' }}">
                            <div>
                                <strong style="font-size: 15px;">{{ $item->product->name ?? 'Producto' }}</strong>
                                <br>
                                <span style="font-size: 13px; color: #6c757d;">
                                    Cantidad: <strong>{{ $item->quantity }}</strong>
                                    @if($item->product->category)
                                        &bull; {{ $item->product->category->name }}
                                    @endif
                                </span>
                            </div>
                            <div>
                                @if($isCompleted)
                                    <span class="badge badge-success px-3 py-2" style="font-size: 14px;">
                                        <i class="fas fa-check mr-1"></i> Terminado
                                    </span>
                                @else
                                    <button type="button" class="btn btn-outline-success btn-sm btn-marcar-terminado"
                                            data-item-id="{{ $item->id }}">
                                        <i class="fas fa-check mr-1"></i> Terminado
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" id="formMarcarListo" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="ready">
                    <button type="submit" class="btn btn-success" id="btnFinalizarPedido" disabled>
                        <i class="fas fa-box-open mr-1"></i> Finalizar Pedido
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalProductosListo');
    if (!modal) return;

    const listaProductos = document.getElementById('listaProductos');
    const progressBar = document.getElementById('progressBar');
    const progressLabel = document.getElementById('progressLabel');
    const btnFinalizar = document.getElementById('btnFinalizarPedido');
    const totalItems = {{ $order->items->count() }};

    function updateProgress() {
        const completados = listaProductos.querySelectorAll('.producto-item[data-completed="1"]').length;
        const porcentaje = totalItems > 0 ? Math.round((completados / totalItems) * 100) : 0;

        progressBar.style.width = porcentaje + '%';
        progressBar.setAttribute('aria-valuenow', porcentaje);
        progressLabel.textContent = completados + ' / ' + totalItems;

        // Habilitar botón solo si todos están terminados
        if (completados >= totalItems) {
            btnFinalizar.disabled = false;
            btnFinalizar.removeAttribute('style');
        } else {
            btnFinalizar.disabled = true;
        }

        if (completados === totalItems) {
            progressBar.classList.remove('bg-success');
            progressBar.classList.add('bg-primary', 'progress-bar-striped', 'progress-bar-animated');
        } else {
            progressBar.classList.remove('bg-primary', 'progress-bar-striped', 'progress-bar-animated');
            progressBar.classList.add('bg-success');
        }
    }

    // Evento click en botones "Terminado"
    listaProductos.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-marcar-terminado');
        if (!btn) return;

        const itemId = btn.dataset.itemId;
        const itemEl = listaProductos.querySelector('.producto-item[data-item-id="' + itemId + '"]');

        // Deshabilitar botón mientras procesa
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...';

        // AJAX para marcar como terminado
        fetch('{{ route("admin.orders.mark-item-completed", $order) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ item_id: itemId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar UI
                itemEl.dataset.completed = '1';
                itemEl.classList.add('bg-light');
                btn.outerHTML = '<span class="badge badge-success px-3 py-2" style="font-size: 14px;"><i class="fas fa-check mr-1"></i> Terminado</span>';
                updateProgress();

                // Toast de éxito
                if (typeof Swal !== 'undefined') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    Toast.fire({ icon: 'success', title: 'Producto marcado como terminado' });
                }
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check mr-1"></i> Terminado';
                alert(data.error || 'Error al marcar producto');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-1"></i> Terminado';
            alert('Error de conexión');
        });
    });

    // Confirmación al finalizar pedido
    document.getElementById('formMarcarListo').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('btnFinalizarPedido');
        const textoOriginal = btn.innerHTML;

        // 1. Click → disabled
        btn.disabled = true;

        // 2. SweetAlert
        Swal.fire({
            title: '¿Finalizar pedido?',
            html: '<p>Esta acción:</p><ul style="text-align:left;"><li>Consume los materiales reservados del inventario</li><li>Marca el pedido como <strong>LISTO</strong></li></ul>',
            icon: 'question',
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check mr-1"></i> Sí, finalizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // 3. Confirma → deshabilitar botones + cargando + envía
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...';
                document.querySelector('#modalProductosListo .btn-secondary').disabled = true;
                form.submit();
            } else {
                // 4. Cancela → estado original
                btn.disabled = false;
                btn.classList.remove('disabled');
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
                btn.innerHTML = textoOriginal;
            }
        });
    });

    // Actualizar progreso al abrir modal
    $(modal).on('shown.bs.modal', function() {
        updateProgress();
    });
});
</script>
@endif
