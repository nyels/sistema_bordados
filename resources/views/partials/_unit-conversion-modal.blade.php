{{--
    Modal de Conversiones de Unidades
    ================================
    Este modal permite al usuario:
    1. Seleccionar un material de la lista disponible
    2. Ver y seleccionar una presentación de compra (factor de conversión)
    3. Aplicar la conversión solo al material seleccionado

    Uso:
    1. Incluir este partial en la vista: @include('partials._unit-conversion-modal')
    2. Llamar: openUnitConversionModal(materialsArray, callback)
       - materialsArray: [{id, name}, ...]
       - callback: function(materialId, conversion) - ejecuta al aplicar
--}}

<style>
/* Z-index alto para que este modal aparezca sobre otros modales */
#unitConversionModal {
    z-index: 1060 !important;
}
#unitConversionModal + .modal-backdrop,
.modal-backdrop.show + #unitConversionModal ~ .modal-backdrop {
    z-index: 1055 !important;
}
</style>

<div class="modal fade" id="unitConversionModal" tabindex="-1" aria-labelledby="unitConversionModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title" id="unitConversionModalLabel">
                    <i class="fas fa-exchange-alt mr-2"></i>Convertir Unidades
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- Paso 1: Seleccionar Material --}}
                <div class="form-group">
                    <label for="conversionMaterialSelect"><strong>1. Seleccionar Material:</strong></label>
                    <select id="conversionMaterialSelect" class="form-control">
                        <option value="">-- Seleccione un material --</option>
                    </select>
                </div>

                {{-- Paso 2: Seleccionar Presentación --}}
                <div class="form-group" id="conversionFactorGroup" style="display: none;">
                    <label for="conversionFactorSelect"><strong>2. Seleccionar Presentacion:</strong></label>
                    <select id="conversionFactorSelect" class="form-control">
                        <option value="">-- Seleccione una presentacion --</option>
                    </select>
                </div>

                {{-- Loading --}}
                <div id="conversionLoading" class="text-center py-3 d-none">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2 mb-0 text-muted">Cargando presentaciones...</p>
                </div>

                {{-- Preview de conversión --}}
                <div id="conversionPreview" class="alert alert-info d-none mt-3">
                    <strong>Conversion:</strong>
                    <span id="conversionPreviewText">-</span>
                </div>

                {{-- Sin datos --}}
                <div id="conversionNoData" class="alert alert-warning d-none mt-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Este material no tiene presentaciones de compra configuradas.
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnApplyConversion" disabled>
                    <i class="fas fa-check mr-1"></i>Convertir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    // Estado del modal
    let availableMaterials = [];
    let currentMaterialId = null;
    let currentConversions = [];
    let selectedConversion = null;
    let onConversionApplied = null;

    // Referencias DOM
    const modal = document.getElementById('unitConversionModal');
    const materialSelect = document.getElementById('conversionMaterialSelect');
    const factorGroup = document.getElementById('conversionFactorGroup');
    const factorSelect = document.getElementById('conversionFactorSelect');
    const loadingEl = document.getElementById('conversionLoading');
    const previewEl = document.getElementById('conversionPreview');
    const previewTextEl = document.getElementById('conversionPreviewText');
    const noDataEl = document.getElementById('conversionNoData');
    const btnApply = document.getElementById('btnApplyConversion');

    /**
     * Inicializa los eventos del modal
     */
    function initModal() {
        if (!materialSelect || !factorSelect || !btnApply) return;

        // Evento al cambiar material
        materialSelect.addEventListener('change', function() {
            const materialId = parseInt(this.value);

            // Reset factor select
            factorSelect.innerHTML = '<option value="">-- Seleccione una presentacion --</option>';
            previewEl.classList.add('d-none');
            noDataEl.classList.add('d-none');
            btnApply.disabled = true;
            selectedConversion = null;
            currentConversions = [];

            if (!materialId) {
                factorGroup.style.display = 'none';
                return;
            }

            currentMaterialId = materialId;
            loadConversions(materialId);
        });

        // Evento al cambiar factor
        factorSelect.addEventListener('change', function() {
            const convId = parseInt(this.value);
            selectedConversion = currentConversions.find(c => c.id === convId) || null;

            if (selectedConversion) {
                previewTextEl.textContent = selectedConversion.display;
                previewEl.classList.remove('d-none');
                btnApply.disabled = false;
            } else {
                previewEl.classList.add('d-none');
                btnApply.disabled = true;
            }
        });

        // Evento al aplicar conversion
        btnApply.addEventListener('click', function() {
            if (currentMaterialId && selectedConversion && typeof onConversionApplied === 'function') {
                onConversionApplied(currentMaterialId, selectedConversion);
                $(modal).modal('hide');
            }
        });

        // Limpiar al cerrar modal
        $(modal).on('hidden.bs.modal', function() {
            resetModal();
            // Si hay otro modal abierto, restaurar el scroll en body
            if ($('.modal.show').length > 0) {
                $('body').addClass('modal-open');
            }
        });

        // Cuando se muestra este modal, asegurar z-index correcto del backdrop
        $(modal).on('shown.bs.modal', function() {
            // Obtener el ultimo backdrop creado y ajustar su z-index
            var $backdrop = $('.modal-backdrop').last();
            $backdrop.css('z-index', 1055);
        });
    }

    /**
     * Resetea el estado del modal
     */
    function resetModal() {
        materialSelect.innerHTML = '<option value="">-- Seleccione un material --</option>';
        factorSelect.innerHTML = '<option value="">-- Seleccione una presentacion --</option>';
        factorGroup.style.display = 'none';
        previewEl.classList.add('d-none');
        noDataEl.classList.add('d-none');
        loadingEl.classList.add('d-none');
        btnApply.disabled = true;
        currentMaterialId = null;
        selectedConversion = null;
        currentConversions = [];
        availableMaterials = [];
    }

    /**
     * Carga las conversiones de un material via AJAX
     */
    function loadConversions(materialId) {
        loadingEl.classList.remove('d-none');
        factorGroup.style.display = 'none';
        noDataEl.classList.add('d-none');

        const url = `/admin/materials/${materialId}/conversions-modal`;

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            loadingEl.classList.add('d-none');

            if (!data.success) {
                throw new Error(data.error || 'Error al cargar conversiones');
            }

            currentConversions = data.conversions || [];
            factorSelect.innerHTML = '<option value="">-- Seleccione una presentacion --</option>';

            if (currentConversions.length === 0) {
                noDataEl.classList.remove('d-none');
                factorGroup.style.display = 'none';
                return;
            }

            // Ordenar presentaciones alfabéticamente (ASC) por label
            const sortedConversions = [...currentConversions].sort((a, b) =>
                (a.label || '').localeCompare(b.label || '', 'es', { sensitivity: 'base' })
            );

            sortedConversions.forEach(conv => {
                const opt = document.createElement('option');
                opt.value = conv.id;
                opt.textContent = conv.label + ' (' + conv.conversion_factor + ' ' + data.consumption_unit + ')';
                factorSelect.appendChild(opt);
            });

            factorGroup.style.display = 'block';
        })
        .catch(err => {
            console.error('Error cargando conversiones:', err);
            loadingEl.classList.add('d-none');
            noDataEl.textContent = 'Error al cargar las presentaciones de compra.';
            noDataEl.classList.remove('d-none');
        });
    }

    /**
     * Abre el modal con la lista de materiales disponibles
     * @param {Array} materials - Array de objetos {id, name} con los materiales disponibles
     * @param {Function} callback - Callback al aplicar conversion: function(materialId, conversion)
     */
    window.openUnitConversionModal = function(materials, callback) {
        if (!materials || materials.length === 0) {
            console.error('Se requiere lista de materiales');
            return;
        }

        resetModal();
        availableMaterials = materials;
        onConversionApplied = callback;

        // Ordenar materiales alfabéticamente (ASC) antes de poblar el select
        const sortedMaterials = [...materials].sort((a, b) =>
            (a.name || '').localeCompare(b.name || '', 'es', { sensitivity: 'base' })
        );

        // Poblar select de materiales
        sortedMaterials.forEach(mat => {
            const opt = document.createElement('option');
            opt.value = mat.id;
            opt.textContent = mat.name;
            materialSelect.appendChild(opt);
        });

        // Si solo hay un material, seleccionarlo automáticamente
        if (materials.length === 1) {
            materialSelect.value = materials[0].id;
            materialSelect.dispatchEvent(new Event('change'));
        }

        $(modal).modal('show');
    };

    // Auto-inicializar cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModal);
    } else {
        initModal();
    }
})();
</script>
