{{-- Partial: Modal de Entrega Rapida (Para index de orders) --}}
<div class="modal fade" id="modalDelivery" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: #007bff; color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-truck mr-2"></i>
                    Registrar Entrega - <span id="deliveryOrderNumber"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="deliveryForm" action="" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="delivered">

                    {{-- Mensaje informativo --}}
                    <div class="alert alert-info mb-3" style="font-size: 14px;">
                        <i class="fas fa-info-circle mr-1"></i>
                        El pedido esta <strong>pagado</strong> y <strong>listo</strong> para entrega.
                    </div>

                    {{-- Campo fecha de entrega --}}
                    <div class="form-group mb-3">
                        <label for="modalDeliveryDate" class="font-weight-bold" style="font-size: 14px;">
                            <i class="fas fa-calendar-alt mr-1"></i> Fecha de Entrega *
                        </label>
                        <input type="date" name="delivered_at" id="modalDeliveryDate"
                               class="form-control" required>
                        <div class="invalid-feedback" id="modalDeliveryDateError">
                            Debe seleccionar la fecha de entrega
                        </div>
                    </div>

                    <div class="text-right mt-4">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="deliverySubmitBtn">
                            <i class="fas fa-truck mr-1"></i> Registrar Entrega
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var currentOrderId = null;

    // Inicializar cuando el DOM este listo
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('deliveryForm');
        var dateInput = document.getElementById('modalDeliveryDate');
        var submitBtn = document.getElementById('deliverySubmitBtn');

        if (!form || !dateInput) return;

        // Input vacio por defecto
        dateInput.value = '';

        // Validar en tiempo real
        function validateDeliveryDate() {
            var value = dateInput.value.trim();
            var isValid = value !== '';

            if (isValid) {
                dateInput.classList.remove('is-invalid');
                dateInput.classList.add('is-valid');
                // Habilitar boton solo si hay fecha
                submitBtn.disabled = false;
            } else {
                dateInput.classList.remove('is-valid');
                dateInput.classList.add('is-invalid');
                // Deshabilitar boton si no hay fecha
                submitBtn.disabled = true;
            }

            return isValid;
        }

        // Eventos de validacion en tiempo real
        dateInput.addEventListener('input', validateDeliveryDate);
        dateInput.addEventListener('change', validateDeliveryDate);
        dateInput.addEventListener('blur', validateDeliveryDate);

        // Boton deshabilitado inicialmente
        submitBtn.disabled = true;

        // Enviar via AJAX
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!validateDeliveryDate()) {
                dateInput.focus();
                return false;
            }

            // Deshabilitar boton
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Registrando...';

            var formData = new FormData(form);

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
                // Cerrar modal
                $('#modalDelivery').modal('hide');

                // Actualizar la fila de la tabla
                updateOrderRowAfterDelivery(currentOrderId);

                // Mostrar SweetAlert2
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Entrega registrada',
                        text: 'El pedido ha sido entregado exitosamente.',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#007bff'
                    });
                }

                // Restaurar boton
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-truck mr-1"></i> Registrar Entrega';
            })
            .catch(function(error) {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-truck mr-1"></i> Registrar Entrega';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al registrar la entrega. Intente de nuevo.',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        });

        // Resetear modal cuando se muestra
        $('#modalDelivery').on('shown.bs.modal', function() {
            setTimeout(function() {
                // Resetear botones (excepto submit que depende de validacion)
                var modal = document.getElementById('modalDelivery');
                var cancelBtn = modal.querySelector('[data-dismiss="modal"]');
                var closeBtn = modal.querySelector('.close');
                if (cancelBtn) {
                    cancelBtn.disabled = false;
                    cancelBtn.removeAttribute('style');
                }
                if (closeBtn) {
                    closeBtn.disabled = false;
                    closeBtn.removeAttribute('style');
                }

                // Resetear submit button HTML pero mantener disabled
                submitBtn.innerHTML = '<i class="fas fa-truck mr-1"></i> Registrar Entrega';

                // Input vacio y sin clases de validacion
                dateInput.value = '';
                dateInput.classList.remove('is-invalid', 'is-valid');

                // Boton deshabilitado hasta que se seleccione fecha
                submitBtn.disabled = true;
            }, 50);
        });

        // Resetear al cerrar
        $('#modalDelivery').on('hidden.bs.modal', function() {
            form.reset();
            dateInput.value = '';
            dateInput.classList.remove('is-invalid', 'is-valid');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-truck mr-1"></i> Registrar Entrega';
        });
    });

    // Actualizar fila de la tabla despues de registrar entrega
    function updateOrderRowAfterDelivery(orderId) {
        // Buscar el boton de entrega de este pedido
        var deliveryBtn = document.querySelector('.btn-quick-delivery[data-order-id="' + orderId + '"]');
        if (!deliveryBtn) return;

        var row = deliveryBtn.closest('tr');
        if (!row) return;

        // Ocultar boton de entrega
        deliveryBtn.style.display = 'none';

        // Actualizar badge de estado en la columna correspondiente (columna 9 - Estado)
        var statusCell = row.querySelector('td:nth-child(9)');
        if (statusCell) {
            var badge = statusCell.querySelector('.badge');
            if (badge) {
                badge.className = 'badge badge-dark';
                badge.style.fontSize = '16px';
                badge.textContent = 'Entregado';
            }
        }
    }

    // Exponer funcion para inicializar el modal
    window.initDeliveryModal = function(orderId, orderNumber) {
        currentOrderId = orderId;

        var form = document.getElementById('deliveryForm');
        var numberEl = document.getElementById('deliveryOrderNumber');
        var dateInput = document.getElementById('modalDeliveryDate');
        var submitBtn = document.getElementById('deliverySubmitBtn');

        if (form) {
            form.action = '/admin/orders/' + orderId + '/status';
        }
        if (numberEl) {
            numberEl.textContent = orderNumber;
        }
        if (dateInput) {
            dateInput.value = '';
            dateInput.classList.remove('is-invalid', 'is-valid');
        }
        if (submitBtn) {
            submitBtn.disabled = true;
        }
    };
})();
</script>
