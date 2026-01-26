{{--
    MODAL: Registrar Merma de Producto Terminado
    PUNTO DE ENTRADA: Inventario de Producto Terminado
    TIPO: finished_product
    UX PASIVA - Sin lógica de negocio
--}}
<div class="modal fade" id="modalWasteFinishedProduct" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #263238; color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-box-open mr-2"></i>
                    Registrar Merma de Producto Terminado
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.waste.store-finished-product') }}" method="POST" id="formWasteFinishedProduct">
                @csrf
                <div class="modal-body">
                    {{-- ADVERTENCIA CANÓNICA --}}
                    <div class="alert mb-3" style="background: #eceff1; border-left: 4px solid #37474f;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle mr-2 mt-1" style="color: #37474f;"></i>
                            <div style="font-size: 14px; color: #37474f;">
                                <strong>Registro Irreversible</strong><br>
                                La merma de producto terminado es un registro contable.
                                <strong>NO reduce el stock automáticamente.</strong>
                                Para ajustar stock físico, use la función de Ajuste en Inventario PT.
                            </div>
                        </div>
                    </div>

                    {{-- PRODUCTO SELECCIONADO --}}
                    <div class="card mb-3">
                        <div class="card-header py-2" style="background: #cfd8dc;">
                            <strong><i class="fas fa-box mr-1"></i> Producto a registrar</strong>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="text-muted small mb-1">Producto:</label>
                                    <p class="mb-1 font-weight-bold" id="wastePtProductName">-</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small mb-1">SKU:</label>
                                    <p class="mb-1" id="wastePtSku">-</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="text-muted small mb-1">Stock:</label>
                                    <p class="mb-1" id="wastePtStock">-</p>
                                </div>
                                <div class="col-md-2">
                                    <label class="text-muted small mb-1">Precio:</label>
                                    <p class="mb-1" id="wastePtPrice">-</p>
                                </div>
                            </div>
                            <input type="hidden" name="product_variant_id" id="wastePtVariantId">
                        </div>
                    </div>

                    {{-- CANTIDAD --}}
                    <div class="form-group">
                        <label for="wastePtQuantity" class="font-weight-bold">
                            Cantidad de Merma <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number"
                                   name="quantity"
                                   id="wastePtQuantity"
                                   class="form-control"
                                   step="1"
                                   min="1"
                                   required
                                   placeholder="Ej: 5">
                            <div class="input-group-append">
                                <span class="input-group-text">unidades</span>
                            </div>
                        </div>
                        <small class="text-muted">Cantidad de unidades de producto terminado perdidas/dañadas</small>
                    </div>

                    {{-- COSTO ESTIMADO (SOLO LECTURA) --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Costo Estimado</label>
                        <div class="p-3 rounded" style="background: #ffebee; border: 1px solid #ef9a9a;">
                            <h4 class="mb-0" id="wastePtEstimatedCost" style="color: #c62828;">$0.00</h4>
                            <small class="text-muted">Calculado automáticamente desde el precio del producto</small>
                        </div>
                    </div>

                    {{-- MOTIVO --}}
                    <div class="form-group">
                        <label for="wastePtReason" class="font-weight-bold">
                            <i class="fas fa-comment-alt mr-1"></i>
                            Motivo de la Merma <span class="text-danger">*</span>
                        </label>
                        <textarea name="reason"
                                  id="wastePtReason"
                                  class="form-control"
                                  rows="3"
                                  required
                                  minlength="5"
                                  maxlength="255"
                                  placeholder="Ej: Producto dañado en almacén, defecto detectado post-producción, producto extraviado..."></textarea>
                        <small class="text-muted">Mínimo 5 caracteres. Este motivo quedará registrado permanentemente.</small>
                    </div>
                </div>
                <div class="modal-footer" style="background: #fafafa;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-dark" id="btnConfirmWastePt">
                        <i class="fas fa-trash-alt mr-1"></i> Registrar Merma PT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Inicializar modal de merma de producto terminado
 * @param {Object} data - Datos del producto seleccionado
 */
function initWasteFinishedProductModal(data) {
    document.getElementById('wastePtVariantId').value = data.variant_id || '';
    document.getElementById('wastePtProductName').textContent = data.name || '-';
    document.getElementById('wastePtSku').textContent = data.sku || '-';
    document.getElementById('wastePtStock').textContent = data.stock || '-';
    document.getElementById('wastePtPrice').textContent = data.price || '-';
    document.getElementById('wastePtQuantity').value = '';
    document.getElementById('wastePtReason').value = '';
    document.getElementById('wastePtEstimatedCost').textContent = '$0.00';

    // Calcular costo estimado al cambiar cantidad
    var qtyInput = document.getElementById('wastePtQuantity');
    var priceValue = parseFloat(data.price_raw) || 0;

    qtyInput.addEventListener('input', function() {
        var qty = parseFloat(this.value) || 0;
        var estimated = qty * priceValue;
        document.getElementById('wastePtEstimatedCost').textContent = '$' + estimated.toFixed(2);
    });
}
</script>
