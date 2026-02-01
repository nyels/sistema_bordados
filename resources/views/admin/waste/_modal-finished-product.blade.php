{{--
    MODAL: Registrar Merma de Producto Terminado
    PUNTO DE ENTRADA: Inventario de Producto Terminado
    TIPO: finished_product
    UX PASIVA - Sin lógica de negocio
--}}
<div class="modal fade" id="modalWasteFinishedProduct" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
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

                    {{-- PRODUCTO SELECCIONADO (RESPONSIVO) --}}
                    <div class="card mb-3">
                        <div class="card-header py-2" style="background: #cfd8dc;">
                            <strong><i class="fas fa-box mr-1"></i> Producto a registrar</strong>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-5 mb-2 mb-md-0">
                                    <label class="text-muted small mb-1">Producto:</label>
                                    <p class="mb-1 font-weight-bold" id="wastePtProductName">-</p>
                                </div>
                                <div class="col-6 col-sm-6 col-md-3 mb-2 mb-md-0">
                                    <label class="text-muted small mb-1">SKU:</label>
                                    <p class="mb-1" id="wastePtSku">-</p>
                                </div>
                                <div class="col-6 col-sm-6 col-md-2 mb-2 mb-md-0">
                                    <label class="text-muted small mb-1">Stock:</label>
                                    <p class="mb-1 font-weight-bold" id="wastePtStock" style="color: #1976d2;">-</p>
                                </div>
                                <div class="col-6 col-sm-6 col-md-2">
                                    <label class="text-muted small mb-1">Costo Prod.:</label>
                                    <p class="mb-1" id="wastePtCost">-</p>
                                </div>
                            </div>
                            <input type="hidden" name="product_variant_id" id="wastePtVariantId">
                            <input type="hidden" id="wastePtStockRaw" value="0">
                        </div>
                    </div>

                    {{-- CANTIDAD Y COSTO EN MISMA FILA (RESPONSIVO) --}}
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                            <div class="form-group mb-0">
                                <label for="wastePtQuantity" class="font-weight-bold">
                                    Cantidad de Merma <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number"
                                           name="quantity"
                                           id="wastePtQuantity"
                                           class="form-control"
                                           style="font-size: 16px;"
                                           step="1"
                                           min="1"
                                           required
                                           placeholder="Ej: 5">
                                    <div class="input-group-append">
                                        <span class="input-group-text">unidades</span>
                                    </div>
                                </div>
                                <small class="text-muted" id="wastePtQtyHelp">Unidades perdidas/dañadas</small>
                                <div id="wastePtQtyError" class="text-danger small mt-1" style="display: none;">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    <span id="wastePtQtyErrorText"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group mb-0">
                                <label class="font-weight-bold">Costo de Producción Perdido</label>
                                <div class="p-3 rounded" style="background: #ffebee; border: 1px solid #ef9a9a;">
                                    <h4 class="mb-0" id="wastePtEstimatedCost" style="color: #c62828;">$0.00</h4>
                                    <small class="text-muted">Calculado desde costo de producción</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- MOTIVO --}}
                    <div class="form-group mt-3">
                        <label for="wastePtReason" class="font-weight-bold">
                            <i class="fas fa-comment-alt mr-1"></i>
                            Motivo de la Merma <span class="text-danger">*</span>
                        </label>
                        <textarea name="reason"
                                  id="wastePtReason"
                                  class="form-control"
                                  style="font-size: 16px;"
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
                    <button type="submit" class="btn btn-dark" id="btnConfirmWastePt" disabled>
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
    // Limpiar listeners previos clonando el input
    var oldQtyInput = document.getElementById('wastePtQuantity');
    var newQtyInput = oldQtyInput.cloneNode(true);
    oldQtyInput.parentNode.replaceChild(newQtyInput, oldQtyInput);

    // Parsear stock (remover comas de formato numérico)
    var stockRaw = parseInt((data.stock || '0').toString().replace(/,/g, ''), 10) || 0;

    // Setear valores iniciales
    document.getElementById('wastePtVariantId').value = data.variant_id || '';
    document.getElementById('wastePtProductName').textContent = data.name || '-';
    document.getElementById('wastePtSku').textContent = data.sku || '-';
    document.getElementById('wastePtStock').textContent = data.stock || '-';
    document.getElementById('wastePtCost').textContent = data.cost || '-';
    document.getElementById('wastePtStockRaw').value = stockRaw;
    newQtyInput.value = '';
    newQtyInput.max = stockRaw;
    document.getElementById('wastePtReason').value = '';
    document.getElementById('wastePtEstimatedCost').textContent = '$0.00';
    document.getElementById('wastePtQtyError').style.display = 'none';
    document.getElementById('btnConfirmWastePt').disabled = true;

    var costValue = parseFloat(data.cost_raw) || 0;

    // Validar y calcular al cambiar cantidad
    newQtyInput.addEventListener('input', function() {
        var qty = parseInt(this.value, 10) || 0;
        var errorDiv = document.getElementById('wastePtQtyError');
        var errorText = document.getElementById('wastePtQtyErrorText');
        var submitBtn = document.getElementById('btnConfirmWastePt');
        var isValid = true;

        // Validar cantidad
        if (qty <= 0) {
            errorDiv.style.display = 'block';
            errorText.textContent = 'La cantidad debe ser mayor a 0';
            isValid = false;
        } else if (qty > stockRaw) {
            errorDiv.style.display = 'block';
            errorText.textContent = 'No puede superar el stock disponible (' + stockRaw + ' unidades)';
            isValid = false;
            this.classList.add('is-invalid');
        } else {
            errorDiv.style.display = 'none';
            this.classList.remove('is-invalid');
        }

        // Calcular costo estimado
        var estimated = qty * costValue;
        document.getElementById('wastePtEstimatedCost').textContent = '$' + estimated.toFixed(2);

        // Habilitar/deshabilitar botón
        submitBtn.disabled = !isValid || qty <= 0;
    });

    // Validar formulario antes de enviar
    document.getElementById('formWasteFinishedProduct').onsubmit = function(e) {
        var qty = parseInt(newQtyInput.value, 10) || 0;
        if (qty <= 0 || qty > stockRaw) {
            e.preventDefault();
            alert('La cantidad de merma debe ser entre 1 y ' + stockRaw + ' unidades.');
            return false;
        }
        return true;
    };
}
</script>
