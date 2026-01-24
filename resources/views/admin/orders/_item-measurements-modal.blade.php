{{-- ================================================================ --}}
{{-- FASE Y: MODAL DE GESTIÓN DE MEDIDAS POR ITEM --}}
{{-- Permite: Capturar nuevas medidas / Seleccionar del historial --}}
{{-- INMUTABLE: Cada captura crea registro en historial --}}
{{-- ================================================================ --}}

@php
    use App\Models\Order;
    $canEditMeasurements = in_array($order->status, [Order::STATUS_DRAFT, Order::STATUS_CONFIRMED]);
@endphp

{{-- MODAL: GESTIÓN DE MEDIDAS (EDITABLE) --}}
@if($canEditMeasurements)
<div class="modal fade" id="itemMeasurementsEditModal" tabindex="-1" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2" style="background: #6f42c1; color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-ruler-combined mr-2"></i>
                    <span id="itemMeasurementsModalTitle">Gestionar Medidas</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                {{-- Info del item --}}
                <div class="alert py-2 mb-3" style="background: #f3e5f5; border: 1px solid #ce93d8;">
                    <i class="fas fa-box mr-1" style="color: #7b1fa2;"></i>
                    <strong style="color: #6a1b9a;">Producto:</strong>
                    <span id="itemMeasurementsProductName" style="color: #4a148c;">-</span>
                </div>

                {{-- Inputs ocultos --}}
                <input type="hidden" id="editingItemId" value="">
                <input type="hidden" id="editingOrderId" value="{{ $order->id }}">
                <input type="hidden" id="editingClienteId" value="{{ $order->cliente_id }}">

                {{-- Selector de origen de medidas --}}
                <div class="mb-3" id="measurementsEditSourceSelector">
                    <label class="font-weight-bold mb-2" style="font-size: 15px; color: #212529;">
                        <i class="fas fa-clipboard-list mr-1"></i> Origen de las medidas:
                    </label>
                    <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                        <label class="btn btn-outline-primary active" id="lblEditNewMeasures" style="font-size: 15px;">
                            <input type="radio" name="editMeasurementSource" value="capture" checked>
                            <i class="fas fa-plus-circle mr-1"></i> Capturar Nuevas
                        </label>
                        <label class="btn btn-outline-primary" id="lblEditExistingMeasures" style="font-size: 15px;">
                            <input type="radio" name="editMeasurementSource" value="select">
                            <i class="fas fa-history mr-1"></i> Usar del Historial
                        </label>
                    </div>
                </div>

                {{-- Panel: Capturar nuevas medidas --}}
                <div id="editNewMeasurementsPanel">
                    <div class="row">
                        {{-- BUSTO --}}
                        <div class="form-group col-md-4 col-6 text-center">
                            <label class="medida-label" style="font-size: 14px; font-weight: 600; color: #495057;">BUSTO</label>
                            <div class="medida-card-edit">
                                <img src="{{ asset('images/busto.png') }}" alt="Busto" class="medida-img-edit">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="editMedBusto" class="form-control text-center medida-input-edit"
                                           placeholder="Ej: 80.5" maxlength="6" inputmode="decimal">
                                    <div class="input-group-append">
                                        <span class="input-group-text" style="font-size: 14px;">cm</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- ALTO CINTURA --}}
                        <div class="form-group col-md-4 col-6 text-center">
                            <label class="medida-label" style="font-size: 14px; font-weight: 600; color: #495057;">ALTO CINTURA</label>
                            <div class="medida-card-edit">
                                <img src="{{ asset('images/alto_cintura.png') }}" alt="Alto Cintura" class="medida-img-edit">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="editMedAltoCintura" class="form-control text-center medida-input-edit"
                                           placeholder="Ej: 40.5" maxlength="6" inputmode="decimal">
                                    <div class="input-group-append">
                                        <span class="input-group-text" style="font-size: 14px;">cm</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- CINTURA --}}
                        <div class="form-group col-md-4 col-6 text-center">
                            <label class="medida-label" style="font-size: 14px; font-weight: 600; color: #495057;">CINTURA</label>
                            <div class="medida-card-edit">
                                <img src="{{ asset('images/cintura.png') }}" alt="Cintura" class="medida-img-edit">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="editMedCintura" class="form-control text-center medida-input-edit"
                                           placeholder="Ej: 70.5" maxlength="6" inputmode="decimal">
                                    <div class="input-group-append">
                                        <span class="input-group-text" style="font-size: 14px;">cm</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- CADERA --}}
                        <div class="form-group col-md-4 col-6 text-center">
                            <label class="medida-label" style="font-size: 14px; font-weight: 600; color: #495057;">CADERA</label>
                            <div class="medida-card-edit">
                                <img src="{{ asset('images/cadera.png') }}" alt="Cadera" class="medida-img-edit">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="editMedCadera" class="form-control text-center medida-input-edit"
                                           placeholder="Ej: 95.5" maxlength="6" inputmode="decimal">
                                    <div class="input-group-append">
                                        <span class="input-group-text" style="font-size: 14px;">cm</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- LARGO BLUSA --}}
                        <div class="form-group col-md-4 col-6 text-center">
                            <label class="medida-label" style="font-size: 14px; font-weight: 600; color: #495057;">LARGO BLUSA</label>
                            <div class="medida-card-edit">
                                <img src="{{ asset('images/largo.png') }}" alt="Largo Blusa" class="medida-img-edit">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="editMedLargo" class="form-control text-center medida-input-edit"
                                           placeholder="Ej: 60.5" maxlength="6" inputmode="decimal">
                                    <div class="input-group-append">
                                        <span class="input-group-text" style="font-size: 14px;">cm</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- LARGO VESTIDO --}}
                        <div class="form-group col-md-4 col-6 text-center">
                            <label class="medida-label" style="font-size: 14px; font-weight: 600; color: #495057;">LARGO VESTIDO</label>
                            <div class="medida-card-edit">
                                <img src="{{ asset('images/largo_vestido.png') }}" alt="Largo Vestido" class="medida-img-edit">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="editMedLargoVestido" class="form-control text-center medida-input-edit"
                                           placeholder="Ej: 120.5" maxlength="6" inputmode="decimal">
                                    <div class="input-group-append">
                                        <span class="input-group-text" style="font-size: 14px;">cm</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Panel: Seleccionar del historial --}}
                <div id="editExistingMeasurementsPanel" style="display: none;">
                    <div class="text-center py-3" id="editHistoryLoading">
                        <i class="fas fa-spinner fa-spin fa-2x" style="color: #6f42c1;"></i>
                        <p class="mt-2 mb-0" style="color: #495057; font-size: 15px;">Cargando historial del cliente...</p>
                    </div>
                    <div id="editHistoryList" class="list-group" style="max-height: 350px; overflow-y: auto;">
                        {{-- Se llena dinámicamente --}}
                    </div>
                    <div class="alert alert-warning py-2 mt-2" id="editNoHistoryAlert" style="display: none; font-size: 14px;">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Este cliente no tiene medidas registradas en el historial. Capture nuevas medidas.
                    </div>
                </div>

                {{-- Preview de medidas seleccionadas del historial --}}
                <div id="selectedHistoryPreview" style="display: none;" class="mt-3 p-3 rounded" style="background: #f3e5f5;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong style="color: #6a1b9a; font-size: 15px;">
                            <i class="fas fa-check-circle mr-1"></i> Medidas seleccionadas:
                        </strong>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearHistorySelection">
                            <i class="fas fa-times mr-1"></i> Cambiar
                        </button>
                    </div>
                    <div id="selectedHistoryContent" class="row small"></div>
                    <input type="hidden" id="selectedHistoryId" value="">
                </div>
            </div>
            <div class="modal-footer py-2" style="background: #f8f9fa;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="font-size: 15px;">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button type="button" class="btn text-white" id="btnSaveItemMeasurements" style="background: #6f42c1; font-size: 15px;">
                    <i class="fas fa-check mr-1"></i> Guardar Medidas
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Estilos para el modal --}}
<style>
    .medida-card-edit {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px;
        background: #ffffff;
        transition: border-color 0.2s ease;
    }
    .medida-card-edit:focus-within {
        border-color: #6f42c1;
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.15);
    }
    .medida-img-edit {
        width: 100%;
        max-height: 60px;
        object-fit: contain;
        margin-bottom: 8px;
    }
    .medida-input-edit:focus {
        border-color: #6f42c1;
        box-shadow: none;
    }
    .history-item {
        cursor: pointer;
        transition: background 0.15s ease;
        border: 2px solid transparent;
    }
    .history-item:hover {
        background: #f3e5f5;
    }
    .history-item.selected {
        background: #ede7f6;
        border-color: #6f42c1;
    }
    .history-item .history-source {
        font-size: 12px;
        padding: 2px 6px;
        border-radius: 3px;
    }
    .history-item .history-source.order { background: #e3f2fd; color: #1565c0; }
    .history-item .history-source.manual { background: #e8f5e9; color: #2e7d32; }
    .history-item .history-source.import { background: #fff3e0; color: #e65100; }
</style>

{{-- JavaScript para el modal --}}
<script>
(function() {
    'use strict';

    var orderId = {{ $order->id }};
    var clienteId = {{ $order->cliente_id }};
    var measurementHistoryCache = null;
    var selectedHistoryRecord = null;

    // Abrir modal de edición de medidas
    window.openItemMeasurementsEdit = function(itemId, itemName, currentMeasurements) {
        document.getElementById('editingItemId').value = itemId;
        document.getElementById('itemMeasurementsProductName').textContent = itemName;
        document.getElementById('itemMeasurementsModalTitle').textContent = 'Gestionar Medidas: ' + itemName;

        // Reset UI
        resetMeasurementsEditModal();

        // Pre-cargar medidas actuales si existen
        if (currentMeasurements && typeof currentMeasurements === 'object') {
            fillMeasurementFields(currentMeasurements);
        }

        $('#itemMeasurementsEditModal').modal('show');
    };

    function resetMeasurementsEditModal() {
        // Reset radio buttons
        document.querySelector('input[name="editMeasurementSource"][value="capture"]').checked = true;
        document.getElementById('lblEditNewMeasures').classList.add('active');
        document.getElementById('lblEditExistingMeasures').classList.remove('active');

        // Reset panels
        document.getElementById('editNewMeasurementsPanel').style.display = 'block';
        document.getElementById('editExistingMeasurementsPanel').style.display = 'none';
        document.getElementById('selectedHistoryPreview').style.display = 'none';

        // Reset inputs
        clearMeasurementFields();

        // Reset history selection
        selectedHistoryRecord = null;
        document.getElementById('selectedHistoryId').value = '';
    }

    function clearMeasurementFields() {
        document.getElementById('editMedBusto').value = '';
        document.getElementById('editMedAltoCintura').value = '';
        document.getElementById('editMedCintura').value = '';
        document.getElementById('editMedCadera').value = '';
        document.getElementById('editMedLargo').value = '';
        document.getElementById('editMedLargoVestido').value = '';
    }

    function fillMeasurementFields(measurements) {
        document.getElementById('editMedBusto').value = measurements.busto || '';
        document.getElementById('editMedAltoCintura').value = measurements.alto_cintura || '';
        document.getElementById('editMedCintura').value = measurements.cintura || '';
        document.getElementById('editMedCadera').value = measurements.cadera || '';
        document.getElementById('editMedLargo').value = measurements.largo || '';
        document.getElementById('editMedLargoVestido').value = measurements.largo_vestido || '';
    }

    function getMeasurementFieldsData() {
        return {
            busto: document.getElementById('editMedBusto').value.trim() || null,
            alto_cintura: document.getElementById('editMedAltoCintura').value.trim() || null,
            cintura: document.getElementById('editMedCintura').value.trim() || null,
            cadera: document.getElementById('editMedCadera').value.trim() || null,
            largo: document.getElementById('editMedLargo').value.trim() || null,
            largo_vestido: document.getElementById('editMedLargoVestido').value.trim() || null
        };
    }

    // Toggle entre capturar y seleccionar del historial
    document.querySelectorAll('input[name="editMeasurementSource"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var mode = this.value;
            if (mode === 'capture') {
                document.getElementById('editNewMeasurementsPanel').style.display = 'block';
                document.getElementById('editExistingMeasurementsPanel').style.display = 'none';
                document.getElementById('selectedHistoryPreview').style.display = 'none';
            } else {
                document.getElementById('editNewMeasurementsPanel').style.display = 'none';
                document.getElementById('editExistingMeasurementsPanel').style.display = 'block';
                loadMeasurementHistory();
            }
        });
    });

    // Cargar historial de medidas
    function loadMeasurementHistory() {
        if (measurementHistoryCache) {
            renderHistoryList(measurementHistoryCache);
            return;
        }

        document.getElementById('editHistoryLoading').style.display = 'block';
        document.getElementById('editHistoryList').innerHTML = '';
        document.getElementById('editNoHistoryAlert').style.display = 'none';

        fetch('/admin/orders/ajax/cliente/' + clienteId + '/measurement-history', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('editHistoryLoading').style.display = 'none';
            if (data.success && data.data && data.data.length > 0) {
                measurementHistoryCache = data.data;
                renderHistoryList(data.data);
            } else {
                document.getElementById('editNoHistoryAlert').style.display = 'block';
            }
        })
        .catch(function() {
            document.getElementById('editHistoryLoading').style.display = 'none';
            document.getElementById('editNoHistoryAlert').style.display = 'block';
        });
    }

    function renderHistoryList(items) {
        var container = document.getElementById('editHistoryList');
        container.innerHTML = '';

        items.forEach(function(item) {
            var sourceClass = item.source === 'order' ? 'order' : (item.source === 'manual' ? 'manual' : 'import');
            var html = '<div class="list-group-item history-item p-3" data-history-id="' + item.id + '">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                    '<div>' +
                        '<span class="history-source ' + sourceClass + '">' + escapeHtml(item.source_label) + '</span>' +
                        (item.order_number ? ' <span class="badge badge-light ml-1" style="font-size: 11px;">' + escapeHtml(item.order_number) + '</span>' : '') +
                        (item.product_name ? '<br><small style="color: #757575;"><i class="fas fa-box mr-1"></i>' + escapeHtml(item.product_name) + '</small>' : '') +
                    '</div>' +
                    '<small style="color: #9e9e9e; font-size: 12px;">' + escapeHtml(item.captured_at_relative || item.captured_at) + '</small>' +
                '</div>' +
                '<div class="mt-2" style="font-size: 14px; color: #212529;">' +
                    '<i class="fas fa-ruler-combined mr-1" style="color: #6f42c1;"></i>' +
                    escapeHtml(item.summary) +
                '</div>' +
            '</div>';

            container.insertAdjacentHTML('beforeend', html);
        });

        // Click handler para seleccionar
        container.querySelectorAll('.history-item').forEach(function(el) {
            el.addEventListener('click', function() {
                selectHistoryItem(parseInt(this.dataset.historyId));
            });
        });
    }

    function selectHistoryItem(historyId) {
        var record = measurementHistoryCache.find(function(r) { return r.id === historyId; });
        if (!record) return;

        selectedHistoryRecord = record;
        document.getElementById('selectedHistoryId').value = historyId;

        // Marcar visualmente
        document.querySelectorAll('.history-item').forEach(function(el) {
            el.classList.remove('selected');
            if (parseInt(el.dataset.historyId) === historyId) {
                el.classList.add('selected');
            }
        });

        // Mostrar preview
        showSelectedHistoryPreview(record);
    }

    function showSelectedHistoryPreview(record) {
        var container = document.getElementById('selectedHistoryContent');
        container.innerHTML = '';

        var measurements = record.measurements || {};
        var labels = {
            busto: 'Busto',
            cintura: 'Cintura',
            cadera: 'Cadera',
            alto_cintura: 'Alto Cintura',
            largo: 'Largo Blusa',
            largo_vestido: 'Largo Vestido'
        };

        for (var key in labels) {
            if (measurements[key]) {
                container.innerHTML += '<div class="col-4 mb-1">' +
                    '<span style="color: #757575;">' + labels[key] + ':</span> ' +
                    '<strong style="color: #6a1b9a;">' + measurements[key] + ' cm</strong>' +
                '</div>';
            }
        }

        document.getElementById('selectedHistoryPreview').style.display = 'block';
        document.getElementById('editExistingMeasurementsPanel').style.display = 'none';
    }

    // Botón para cambiar selección del historial
    document.getElementById('btnClearHistorySelection')?.addEventListener('click', function() {
        selectedHistoryRecord = null;
        document.getElementById('selectedHistoryId').value = '';
        document.getElementById('selectedHistoryPreview').style.display = 'none';
        document.getElementById('editExistingMeasurementsPanel').style.display = 'block';
        document.querySelectorAll('.history-item').forEach(function(el) {
            el.classList.remove('selected');
        });
    });

    // Guardar medidas
    document.getElementById('btnSaveItemMeasurements')?.addEventListener('click', function() {
        var itemId = document.getElementById('editingItemId').value;
        var mode = document.querySelector('input[name="editMeasurementSource"]:checked').value;
        var payload = {};

        if (mode === 'select') {
            // Modo selección de historial
            var historyId = document.getElementById('selectedHistoryId').value;
            if (!historyId) {
                Swal.fire('Error', 'Debe seleccionar un registro del historial.', 'warning');
                return;
            }
            payload = {
                mode: 'select',
                measurement_history_id: parseInt(historyId)
            };
        } else {
            // Modo captura
            var measurements = getMeasurementFieldsData();
            var hasAny = Object.values(measurements).some(function(v) { return v !== null && v !== ''; });
            if (!hasAny) {
                Swal.fire('Error', 'Debe capturar al menos una medida.', 'warning');
                return;
            }
            payload = {
                mode: 'capture',
                measurements: measurements,
                save_to_history: true // Siempre guardar en historial al crear pedido
            };
        }

        Swal.fire({
            title: 'Guardando medidas...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: function() { Swal.showLoading(); }
        });

        fetch('/admin/orders/' + orderId + '/items/' + itemId + '/measurements', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                Swal.fire({
                    title: '¡Guardado!',
                    text: data.message,
                    icon: 'success',
                    timer: 2000,
                    timerProgressBar: true
                }).then(function() {
                    $('#itemMeasurementsEditModal').modal('hide');
                    window.location.reload();
                });
            } else {
                Swal.fire('Error', data.message || 'No se pudieron guardar las medidas.', 'error');
            }
        })
        .catch(function() {
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
</script>
@endif
