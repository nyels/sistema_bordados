{{-- Partial: Modal de Pago (Reutilizable en index y show) --}}
<div class="modal fade" id="modalPayment" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="paymentForm" action="" method="POST">
                @csrf
                <div class="modal-header" style="background: #343a40; color: white;">
                    <h5 class="modal-title">
                        Registrar Pago - <span id="paymentOrderNumber"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        Saldo pendiente: <strong id="paymentBalance" style="font-size: 18px;">$0.00</strong>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Monto *</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                            <input type="number" name="amount" id="paymentAmount" class="form-control"
                                   step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Metodo *</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash">Efectivo</option>
                            <option value="transfer">Transferencia</option>
                            <option value="card">Tarjeta</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Referencia</label>
                        <input type="text" name="reference" id="paymentReference" class="form-control"
                               placeholder="No. operacion, folio, etc.">
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea name="notes" id="paymentNotes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Guardar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
