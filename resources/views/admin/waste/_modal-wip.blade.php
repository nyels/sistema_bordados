{{--
    MODAL: Registrar Merma en Proceso (WIP)
    PUNTO DE ENTRADA: Pedido en Producción (IN_PRODUCTION)
    TIPO: wip
    UX PASIVA - Sin lógica de negocio
    VARIABLE REQUERIDA: $order
--}}
@php
    $orderId = $order->id ?? 0;
    $orderNumber = $order->order_number ?? 'N/A';
@endphp
<div class="modal fade" id="modalWasteWip" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #c62828; color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-industry mr-2"></i>
                    Registrar Merma en Proceso
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.waste.store-wip', $orderId) }}" method="POST" id="formWasteWip">
                @csrf
                <div class="modal-body">
                    {{-- ADVERTENCIA CANÓNICA --}}
                    <div class="alert mb-3" style="background: #ffebee; border-left: 4px solid #c62828;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle mr-2 mt-1" style="color: #c62828;"></i>
                            <div style="font-size: 14px; color: #5d4037;">
                                <strong>Registro Irreversible de Merma en Proceso</strong><br>
                                Este registro documenta materiales perdidos durante la producción.
                                <strong>NO revierte el pedido ni ajusta inventario automáticamente.</strong>
                            </div>
                        </div>
                    </div>

                    {{-- PEDIDO --}}
                    <div class="card mb-3" style="border: 1px solid #90caf9;">
                        <div class="card-header py-2" style="background: #e3f2fd;">
                            <strong><i class="fas fa-clipboard-list mr-1"></i> Pedido Asociado</strong>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="text-muted small mb-1">Número:</label>
                                    <p class="mb-1 font-weight-bold">{{ $orderNumber }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small mb-1">Estado:</label>
                                    <p class="mb-1">
                                        <span class="badge badge-{{ $order->status_color ?? 'secondary' }}">
                                            {{ $order->status_label ?? 'N/A' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- MATERIALES (Selección desde BOM visual) --}}
                    <div class="form-group">
                        <label class="font-weight-bold">
                            <i class="fas fa-cubes mr-1"></i>
                            Materiales Afectados
                            <small class="text-muted">(opcional)</small>
                        </label>
                        <div class="alert alert-info py-2 mb-2" style="font-size: 13px;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Si la merma incluye materiales, especifique cuáles y en qué cantidad.
                            Puede dejar vacío si es solo falla de mano de obra.
                        </div>

                        {{-- Contenedor para materiales dinámicos --}}
                        <div id="wipMaterialsContainer">
                            {{-- Se agregarán filas dinámicamente --}}
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAddWipMaterial">
                            <i class="fas fa-plus mr-1"></i> Agregar Material
                        </button>
                    </div>

                    {{-- MOTIVO --}}
                    <div class="form-group">
                        <label for="wasteWipReason" class="font-weight-bold">
                            <i class="fas fa-comment-alt mr-1"></i>
                            Motivo de la Merma <span class="text-danger">*</span>
                        </label>
                        <textarea name="reason"
                                  id="wasteWipReason"
                                  class="form-control"
                                  rows="3"
                                  required
                                  minlength="5"
                                  maxlength="255"
                                  placeholder="Ej: Error en bordado, falla de máquina, material defectuoso descubierto durante producción..."></textarea>
                        <small class="text-muted">Mínimo 5 caracteres. Describa la causa de la pérdida en producción.</small>
                    </div>
                </div>
                <div class="modal-footer" style="background: #fafafa;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmWasteWip">
                        <i class="fas fa-trash-alt mr-1"></i> Registrar Merma WIP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var wipMaterialIndex = 0;
    var container = document.getElementById('wipMaterialsContainer');
    var btnAdd = document.getElementById('btnAddWipMaterial');

    if (btnAdd) {
        btnAdd.addEventListener('click', function() {
            var row = document.createElement('div');
            row.className = 'row mb-2 wip-material-row';
            row.innerHTML = `
                <div class="col-md-5">
                    <input type="number" name="materials[${wipMaterialIndex}][material_variant_id]"
                           class="form-control form-control-sm" placeholder="ID Variante Material">
                </div>
                <div class="col-md-4">
                    <input type="number" name="materials[${wipMaterialIndex}][quantity]"
                           class="form-control form-control-sm" step="0.01" min="0.01" placeholder="Cantidad">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-wip-material">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
            wipMaterialIndex++;

            // Evento para remover
            row.querySelector('.btn-remove-wip-material').addEventListener('click', function() {
                row.remove();
            });
        });
    }
});
</script>
