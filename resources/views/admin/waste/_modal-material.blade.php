{{--
    MODAL: Registrar Merma de Material
    PUNTO DE ENTRADA: Inventario de Materiales
    TIPO: material
    UX PASIVA - Sin lógica de negocio
--}}
<div class="modal fade" id="modalWasteMaterial" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #e65100; color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-cubes mr-2"></i>
                    Registrar Merma de Material
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.waste.store-material') }}" method="POST" id="formWasteMaterial">
                @csrf
                <div class="modal-body">
                    {{-- ADVERTENCIA --}}
                    <div class="alert mb-3" style="background: #fff3e0; border-left: 4px solid #f57c00;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle mr-2 mt-1" style="color: #e65100;"></i>
                            <div style="font-size: 14px; color: #5d4037;">
                                <strong>Registro Irreversible</strong><br>
                                Una vez registrada, no podrá editarse ni eliminarse.
                            </div>
                        </div>
                    </div>

                    {{-- MATERIAL SELECCIONADO --}}
                    <div class="card mb-3">
                        <div class="card-header py-2" style="background: #eceff1;">
                            <strong><i class="fas fa-layer-group mr-1"></i> Material a registrar</strong>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="text-muted small mb-1">Material:</label>
                                    <p class="mb-1 font-weight-bold" id="wasteMaterialName">-</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small mb-1">Stock Actual:</label>
                                    <p class="mb-1" id="wasteMaterialStock">-</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="text-muted small mb-1">Costo Prom.:</label>
                                    <p class="mb-1" id="wasteMaterialCost">-</p>
                                </div>
                            </div>
                            <input type="hidden" name="materials[0][material_variant_id]" id="wasteMaterialVariantId">
                        </div>
                    </div>

                    {{-- CANTIDAD Y MOTIVO EN LA MISMA FILA --}}
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="wasteMaterialQuantity" class="font-weight-bold">
                                    Cantidad de Merma <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number"
                                           name="materials[0][quantity]"
                                           id="wasteMaterialQuantity"
                                           class="form-control"
                                           step="0.01"
                                           min="0.01"
                                           required
                                           placeholder="Ej: 5.00">
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="wasteMaterialUnit">unidades</span>
                                    </div>
                                </div>
                                <small class="text-muted">Material perdido/dañado</small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="wasteMaterialReason" class="font-weight-bold">
                                    <i class="fas fa-comment-alt mr-1"></i>
                                    Motivo de la Merma <span class="text-danger">*</span>
                                </label>
                                <textarea name="reason"
                                          id="wasteMaterialReason"
                                          class="form-control"
                                          rows="2"
                                          required
                                          minlength="5"
                                          maxlength="255"
                                          placeholder="Ej: Material dañado por humedad, defecto de fábrica, material caducado..."></textarea>
                                <small class="text-muted">Mínimo 5 caracteres. Este motivo quedará registrado permanentemente.</small>
                            </div>
                        </div>
                    </div>

                    {{-- NOTAS ADICIONALES --}}
                    <div class="form-group mb-0">
                        <label for="wasteMaterialNotes">
                            Notas adicionales <small class="text-muted">(opcional)</small>
                        </label>
                        <input type="text"
                               name="materials[0][notes]"
                               id="wasteMaterialNotes"
                               class="form-control"
                               maxlength="255"
                               placeholder="Ej: Lote #123, ubicación estante A2...">
                    </div>
                </div>
                <div class="modal-footer" style="background: #fafafa;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn text-white" id="btnConfirmWasteMaterial" style="background: #e65100;">
                        <i class="fas fa-trash-alt mr-1"></i> Registrar Merma
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Inicializar modal de merma de material
 * @param {Object} data - Datos del material seleccionado
 */
function initWasteMaterialModal(data) {
    document.getElementById('wasteMaterialVariantId').value = data.variant_id || '';
    document.getElementById('wasteMaterialName').textContent = data.name || '-';
    document.getElementById('wasteMaterialStock').textContent = data.stock || '-';
    document.getElementById('wasteMaterialCost').textContent = data.cost || '-';
    document.getElementById('wasteMaterialUnit').textContent = data.unit || 'unidades';
    document.getElementById('wasteMaterialQuantity').value = '';
    document.getElementById('wasteMaterialReason').value = '';
    document.getElementById('wasteMaterialNotes').value = '';
}
</script>
