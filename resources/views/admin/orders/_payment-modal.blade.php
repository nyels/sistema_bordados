{{-- Partial: Modal de Pago con Pestañas (Reutilizable en index y show) --}}
<div class="modal fade" id="modalPayment" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: #343a40; color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-dollar-sign mr-2"></i>
                    Pagos - <span id="paymentOrderNumber"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0">
                {{-- Pestañas --}}
                <ul class="nav nav-tabs nav-justified" id="paymentTabs" role="tablist" style="background: #f8f9fa;">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-new-payment" data-toggle="tab" href="#pane-new-payment" role="tab">
                            <i class="fas fa-plus-circle mr-1"></i> Registrar Pago
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-payment-history" data-toggle="tab" href="#pane-payment-history" role="tab">
                            <i class="fas fa-history mr-1"></i> Historial
                            <span class="badge badge-secondary ml-1" id="paymentHistoryCount">0</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- TAB: Registrar Pago --}}
                    <div class="tab-pane fade show active p-3" id="pane-new-payment" role="tabpanel">
                        <form id="paymentForm" action="" method="POST">
                            @csrf
                            {{-- Saldo pendiente - se actualiza en tiempo real --}}
                            <div class="alert mb-3" id="paymentBalanceAlert">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span id="paymentBalanceLabel">Saldo pendiente:</span>
                                    <strong id="paymentBalance" style="font-size: 20px;">$0.00</strong>
                                </div>
                            </div>

                            {{-- Alerta de error si excede el saldo --}}
                            <div class="alert alert-danger mb-3" id="paymentErrorAlert" style="display: none;">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <span id="paymentErrorMessage">El monto no puede ser mayor al saldo pendiente.</span>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Monto *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                            <input type="number" name="amount" id="paymentAmount" class="form-control"
                                                   step="0.01" min="0.01" required>
                                        </div>
                                        <small class="form-text text-muted" id="paymentMaxHint">Máximo: $0.00</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Metodo *</label>
                                        <select name="payment_method" id="paymentMethod" class="form-control" required>
                                            <option value="cash">Efectivo</option>
                                            <option value="transfer">Transferencia</option>
                                            <option value="card">Tarjeta</option>
                                            <option value="other">Otro</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Referencia</label>
                                        <input type="text" name="reference" id="paymentReference" class="form-control"
                                               placeholder="No. operacion, folio, etc.">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Notas</label>
                                        <input type="text" name="notes" id="paymentNotes" class="form-control"
                                               placeholder="Observaciones">
                                    </div>
                                </div>
                            </div>

                            <div class="text-right mt-3">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success" id="paymentSubmitBtn">
                                    <i class="fas fa-save mr-1"></i> Guardar Pago
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- TAB: Historial de Pagos --}}
                    <div class="tab-pane fade p-3" id="pane-payment-history" role="tabpanel">
                        <div id="paymentHistoryLoading" class="text-center py-4" style="display: none;">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2 mb-0" style="font-size: 16px;">Cargando historial...</p>
                        </div>
                        <div id="paymentHistoryEmpty" class="text-center py-4" style="display: none;">
                            <i class="fas fa-inbox fa-3x text-muted"></i>
                            <p class="mt-2 mb-0 text-muted" style="font-size: 16px;">No hay pagos registrados</p>
                        </div>
                        <div id="paymentHistoryContent" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" style="font-size: 16px;">
                                    <thead style="background: #343a40; color: white;">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Monto</th>
                                            <th>Metodo</th>
                                            <th>Referencia</th>
                                            <th>Notas</th>
                                            <th>Registrado por</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paymentHistoryTable"></tbody>
                                </table>
                            </div>
                            <div class="bg-light p-3 mt-2 rounded d-flex justify-content-between" style="font-size: 17px;">
                                <span><strong>Total Pagado:</strong></span>
                                <strong id="paymentHistoryTotal" class="text-success" style="font-size: 18px;">$0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var originalBalance = 0;
    var currentOrderId = null;
    var isAjaxMode = false; // Para el index, usar AJAX sin redirigir

    // Función para resetear completamente el modal
    function resetPaymentModal() {
        var modal = document.getElementById('modalPayment');
        var form = document.getElementById('paymentForm');
        var amountInput = document.getElementById('paymentAmount');
        var errorAlert = document.getElementById('paymentErrorAlert');
        var balanceAlert = document.getElementById('paymentBalanceAlert');
        var balanceLabel = document.getElementById('paymentBalanceLabel');
        var balanceEl = document.getElementById('paymentBalance');

        // CRÍTICO: Resetear TODOS los botones del modal
        if (modal) {
            var allButtons = modal.querySelectorAll('button');
            allButtons.forEach(function(btn) {
                btn.disabled = false;
                btn.removeAttribute('style');
                btn.classList.remove('disabled');
            });
        }

        // Asegurar HTML correcto del botón submit
        var submitBtn = document.getElementById('paymentSubmitBtn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-save mr-1"></i> Guardar Pago';
        }

        // Resetear formulario
        if (form) form.reset();

        // Resetear campos específicos
        if (amountInput) {
            amountInput.value = '';
            amountInput.classList.remove('is-invalid');
        }

        // Resetear alertas
        if (errorAlert) errorAlert.style.display = 'none';

        // Resetear saldo display
        if (balanceAlert) balanceAlert.className = 'alert alert-info mb-3';
        if (balanceLabel) balanceLabel.textContent = 'Saldo pendiente:';
        if (balanceEl) balanceEl.textContent = '$' + originalBalance.toFixed(2);

        // Resetear historial
        var contentEl = document.getElementById('paymentHistoryContent');
        var emptyEl = document.getElementById('paymentHistoryEmpty');
        var loadingEl = document.getElementById('paymentHistoryLoading');
        if (contentEl) contentEl.style.display = 'none';
        if (emptyEl) emptyEl.style.display = 'none';
        if (loadingEl) loadingEl.style.display = 'none';

        // Resetear a la primera pestaña
        $('#tab-new-payment').tab('show');
    }

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        var amountInput = document.getElementById('paymentAmount');
        if (amountInput) {
            amountInput.addEventListener('input', updatePaymentPreview);
            amountInput.addEventListener('change', updatePaymentPreview);
        }

        // Validación y envío del formulario
        var form = document.getElementById('paymentForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                var amount = parseFloat(amountInput.value) || 0;
                if (amount <= 0) {
                    e.preventDefault();
                    showPaymentError('El monto debe ser mayor a $0.00');
                    return false;
                }
                if (amount > originalBalance) {
                    e.preventDefault();
                    showPaymentError('El monto no puede ser mayor al saldo pendiente ($' + originalBalance.toFixed(2) + ')');
                    return false;
                }

                // Si estamos en modo AJAX (desde index), enviar via AJAX
                if (isAjaxMode) {
                    e.preventDefault();
                    submitPaymentAjax(form);
                    return false;
                }
                // Si no, dejar que el form se envíe normalmente (desde show)
            });
        }

        // Cargar historial al cambiar a la pestaña
        var historyTab = document.getElementById('tab-payment-history');
        if (historyTab) {
            $(historyTab).on('shown.bs.tab', loadPaymentHistory);
        }

        // CRÍTICO: Resetear modal cuando se muestra (después de que Bootstrap lo muestre)
        $('#modalPayment').on('shown.bs.modal', function() {
            // Usar setTimeout para asegurar que Bootstrap termine sus animaciones
            setTimeout(function() {
                // Resetear TODOS los botones del modal
                var modal = document.getElementById('modalPayment');
                var allButtons = modal.querySelectorAll('button');
                allButtons.forEach(function(btn) {
                    btn.disabled = false;
                    btn.removeAttribute('style');
                    btn.classList.remove('disabled');
                });

                // Asegurar HTML correcto del botón submit
                var submitBtn = document.getElementById('paymentSubmitBtn');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-save mr-1"></i> Guardar Pago';
                }
            }, 50);
        });

        // Resetear modal cuando se oculta (para la próxima vez)
        $('#modalPayment').on('hidden.bs.modal', function() {
            resetPaymentModal();
        });
    });

    // Enviar pago via AJAX (para el index)
    function submitPaymentAjax(form) {
        var submitBtn = document.getElementById('paymentSubmitBtn');
        var formData = new FormData(form);
        var amountPaid = parseFloat(document.getElementById('paymentAmount').value) || 0;

        // Deshabilitar botón
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) {
            if (response.redirected) {
                return { success: true, redirected: true };
            }
            return response.json().catch(function() {
                return { success: true };
            });
        })
        .then(function(data) {
            // Actualizar el nuevo saldo
            var newBalance = originalBalance - amountPaid;

            // Actualizar la fila de la tabla si existe
            updateOrderRowAfterPayment(currentOrderId, newBalance);

            // Resetear formulario
            form.reset();
            document.getElementById('paymentAmount').value = '';

            // Actualizar saldo interno
            originalBalance = newBalance;

            // SIEMPRE cerrar el modal después de registrar pago
            $('#modalPayment').modal('hide');

            // Mostrar SweetAlert2 completo según el resultado
            if (newBalance <= 0) {
                showSuccessAlert('Pago registrado', 'El pedido ha sido pagado completamente.');
            } else {
                showSuccessAlert(
                    'Pago registrado',
                    'Se registró un pago de $' + amountPaid.toFixed(2) + '.\nSaldo pendiente: $' + newBalance.toFixed(2)
                );
            }

            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save mr-1"></i> Guardar Pago';
        })
        .catch(function(error) {
            console.error('Error:', error);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save mr-1"></i> Guardar Pago';
            showPaymentError('Error al guardar el pago. Intente de nuevo.');
        });
    }

    // Mostrar SweetAlert2 completo (no toast)
    function showSuccessAlert(title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: title,
                text: text,
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#28a745'
            });
        }
    }

    // Actualizar fila de la tabla después de registrar pago
    function updateOrderRowAfterPayment(orderId, newBalance) {
        // Buscar el botón de pago de este pedido
        var paymentBtn = document.querySelector('.btn-quick-payment[data-order-id="' + orderId + '"]');
        if (!paymentBtn) return;

        var row = paymentBtn.closest('tr');
        if (!row) return;

        // Actualizar data-balance del botón
        paymentBtn.dataset.balance = newBalance;

        // Si balance es 0 o menos, ocultar botón de pago y actualizar badge de estado de pago
        if (newBalance <= 0) {
            paymentBtn.style.display = 'none';

            // Actualizar badge de estado de pago en la columna correspondiente
            var paymentCell = row.querySelector('td:nth-child(8)'); // Columna de Pago
            if (paymentCell) {
                var badge = paymentCell.querySelector('.badge');
                if (badge) {
                    badge.className = 'badge badge-success';
                    badge.style.fontSize = '16px';
                    badge.textContent = 'Pagado';
                }
            }
        }
    }

    // Función para cargar historial de pagos
    function loadPaymentHistory() {
        if (!currentOrderId) return;

        var loadingEl = document.getElementById('paymentHistoryLoading');
        var emptyEl = document.getElementById('paymentHistoryEmpty');
        var contentEl = document.getElementById('paymentHistoryContent');
        var tableBody = document.getElementById('paymentHistoryTable');
        var totalEl = document.getElementById('paymentHistoryTotal');
        var countEl = document.getElementById('paymentHistoryCount');

        // Mostrar loading
        loadingEl.style.display = 'block';
        emptyEl.style.display = 'none';
        contentEl.style.display = 'none';

        // Obtener URL base
        var baseUrl = window.location.origin + '/admin/orders/' + currentOrderId + '/payments';

        fetch(baseUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            loadingEl.style.display = 'none';

            if (data.payments && data.payments.length > 0) {
                // Construir tabla
                var html = '';
                data.payments.forEach(function(payment) {
                    html += '<tr>';
                    html += '<td class="text-nowrap">' + payment.created_at + '</td>';
                    html += '<td class="font-weight-bold text-success">$' + payment.amount.toFixed(2) + '</td>';
                    html += '<td><i class="fas ' + payment.method_icon + ' mr-1"></i> ' + payment.method_label + '</td>';
                    html += '<td>' + (payment.reference || '<span class="text-muted">-</span>') + '</td>';
                    html += '<td>' + (payment.notes || '<span class="text-muted">-</span>') + '</td>';
                    html += '<td>' + payment.received_by + '</td>';
                    html += '</tr>';
                });
                tableBody.innerHTML = html;
                totalEl.textContent = '$' + data.total_paid.toFixed(2);
                countEl.textContent = data.payments.length;
                contentEl.style.display = 'block';
            } else {
                emptyEl.style.display = 'block';
                countEl.textContent = '0';
            }
        })
        .catch(function(error) {
            loadingEl.style.display = 'none';
            emptyEl.style.display = 'block';
            console.error('Error loading payment history:', error);
        });
    }

    // Función para actualizar el saldo pendiente en tiempo real
    function updatePaymentPreview() {
        var amountInput = document.getElementById('paymentAmount');
        var balanceAlert = document.getElementById('paymentBalanceAlert');
        var balanceLabel = document.getElementById('paymentBalanceLabel');
        var balanceEl = document.getElementById('paymentBalance');
        var errorAlert = document.getElementById('paymentErrorAlert');
        var submitBtn = document.getElementById('paymentSubmitBtn');

        var amount = parseFloat(amountInput.value) || 0;
        var newBalance = originalBalance - amount;

        // Validar que no exceda el saldo
        if (amount > originalBalance) {
            document.getElementById('paymentErrorMessage').textContent = 'El monto no puede ser mayor al saldo pendiente ($' + originalBalance.toFixed(2) + ')';
            errorAlert.style.display = 'block';
            amountInput.classList.add('is-invalid');
            submitBtn.disabled = true;
            balanceAlert.className = 'alert alert-info mb-3';
            balanceLabel.textContent = 'Saldo pendiente:';
            balanceEl.textContent = '$' + originalBalance.toFixed(2);
            return;
        }

        // Ocultar error
        errorAlert.style.display = 'none';
        amountInput.classList.remove('is-invalid');
        submitBtn.disabled = false;

        // Si no hay monto ingresado, mostrar saldo original
        if (amount <= 0) {
            balanceAlert.className = 'alert alert-info mb-3';
            balanceLabel.textContent = 'Saldo pendiente:';
            balanceEl.textContent = '$' + originalBalance.toFixed(2);
            return;
        }

        // Actualizar saldo pendiente en tiempo real
        balanceEl.textContent = '$' + newBalance.toFixed(2);

        // Cambiar etiqueta y color según el nuevo saldo
        if (newBalance <= 0) {
            balanceAlert.className = 'alert alert-success mb-3';
            balanceLabel.textContent = 'Nuevo saldo:';
        } else {
            balanceAlert.className = 'alert alert-warning mb-3';
            balanceLabel.textContent = 'Nuevo saldo:';
        }
    }

    // Función para mostrar error
    function showPaymentError(message) {
        var errorAlert = document.getElementById('paymentErrorAlert');
        var errorMessage = document.getElementById('paymentErrorMessage');
        if (errorAlert && errorMessage) {
            errorMessage.textContent = message;
            errorAlert.style.display = 'block';
        }
    }

    // Exponer función para establecer el saldo original y el order ID
    window.setPaymentOriginalBalance = function(balance, orderId, useAjax) {
        originalBalance = parseFloat(balance) || 0;
        currentOrderId = orderId || null;
        isAjaxMode = useAjax === true;

        // Actualizar hint de máximo
        var maxHint = document.getElementById('paymentMaxHint');
        if (maxHint) {
            maxHint.textContent = 'Máximo: $' + originalBalance.toFixed(2);
        }

        // Actualizar max del input
        var amountInput = document.getElementById('paymentAmount');
        if (amountInput) {
            amountInput.max = originalBalance;
        }

        // Llamar a reset completo del modal
        resetPaymentModal();
    };
})();
</script>
