/**
 * SearchModule - Búsqueda AJAX en tiempo real
 * 
 * Características:
 * - Debounce para evitar requests excesivos
 * - Autocompletado con dropdown
 * - Actualización de resultados sin recargar página
 * - Loading states
 * - Manejo de errores
 * 
 * @version 1.0
 */
const SearchModule = (function() {
    'use strict';

    // Configuración
    const CONFIG = {
        debounceMs: 300,        // Espera antes de buscar
        minChars: 2,            // Mínimo de caracteres
        maxAutocomplete: 10,    // Máximo sugerencias
        endpoints: {
            search: '/admin/search/ajax',
            autocomplete: '/admin/search/autocomplete'
        }
    };

    // Estado
    let debounceTimer = null;
    let currentRequest = null;
    let isInitialized = false;

    /**
     * Inicializar módulo de búsqueda.
     * @param {Object} options - Opciones de configuración
     */
    function init(options = {}) {
        if (isInitialized) return;

        // Merge opciones
        Object.assign(CONFIG, options);

        // Buscar elementos
        const searchInput = document.querySelector('[data-search-input]') || 
                           document.querySelector('#searchInput') ||
                           document.querySelector('input[name="search"]');
        
        if (!searchInput) {
            console.warn('SearchModule: No se encontró input de búsqueda');
            return;
        }

        // Configurar eventos
        setupSearchInput(searchInput);
        setupAutocomplete(searchInput);

        isInitialized = true;
        console.log('SearchModule: Inicializado');
    }

    /**
     * Configurar input de búsqueda principal.
     */
    function setupSearchInput(input) {
        // Prevenir submit del form si existe
        const form = input.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Solo prevenir si es búsqueda AJAX
                if (input.dataset.ajaxSearch !== 'false') {
                    e.preventDefault();
                    executeSearch(input.value);
                }
            });
        }

        // Evento de input con debounce
        input.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            
            // Limpiar timer anterior
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }

            // Si está vacío, mostrar todos o limpiar
            if (query.length === 0) {
                clearResults();
                hideAutocomplete();
                return;
            }

            // Si es muy corto, solo autocomplete
            if (query.length < CONFIG.minChars) {
                hideAutocomplete();
                return;
            }

            // Debounce para autocompletado
            debounceTimer = setTimeout(() => {
                fetchAutocomplete(query);
            }, CONFIG.debounceMs / 2);
        });

        // Tecla Enter ejecuta búsqueda
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                hideAutocomplete();
                executeSearch(input.value);
            }
            
            // Escape cierra autocompletado
            if (e.key === 'Escape') {
                hideAutocomplete();
            }
        });

        // Click fuera cierra autocompletado
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !e.target.closest('.autocomplete-dropdown')) {
                hideAutocomplete();
            }
        });
    }

    /**
     * Configurar dropdown de autocompletado.
     */
    function setupAutocomplete(input) {
        // Crear dropdown si no existe
        let dropdown = document.querySelector('.autocomplete-dropdown');
        
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.className = 'autocomplete-dropdown';
            dropdown.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                max-height: 400px;
                overflow-y: auto;
                z-index: 1000;
                display: none;
            `;
            
            // Posicionar relativo al input
            const wrapper = input.parentElement;
            wrapper.style.position = 'relative';
            wrapper.appendChild(dropdown);
        }

        input._autocompleteDropdown = dropdown;
    }

    /**
     * Fetch autocompletado desde el servidor.
     */
    async function fetchAutocomplete(query) {
        if (query.length < CONFIG.minChars) return;

        try {
            const response = await fetch(
                `${CONFIG.endpoints.autocomplete}?term=${encodeURIComponent(query)}&limit=${CONFIG.maxAutocomplete}`,
                {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            if (!response.ok) throw new Error('Network response was not ok');

            const suggestions = await response.json();
            renderAutocomplete(suggestions, query);

        } catch (error) {
            console.error('SearchModule: Error en autocomplete', error);
            hideAutocomplete();
        }
    }

    /**
     * Renderizar dropdown de autocompletado.
     */
    function renderAutocomplete(suggestions, query) {
        const input = document.querySelector('[data-search-input], #searchInput, input[name="search"]');
        const dropdown = input?._autocompleteDropdown;
        
        if (!dropdown || !suggestions.length) {
            hideAutocomplete();
            return;
        }

        const html = suggestions.map((item, index) => `
            <div class="autocomplete-item" 
                 data-id="${item.id}" 
                 data-value="${escapeHtml(item.name)}"
                 tabindex="${index}"
                 style="
                    display: flex;
                    align-items: center;
                    padding: 10px 12px;
                    cursor: pointer;
                    border-bottom: 1px solid #f3f4f6;
                    transition: background 0.15s;
                 "
                 onmouseover="this.style.background='#f3f4f6'"
                 onmouseout="this.style.background='white'">
                ${item.image ? `
                    <img src="${item.image}" 
                         alt="" 
                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; margin-right: 12px;"
                         onerror="this.style.display='none'">
                ` : `
                    <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 6px; margin-right: 12px; display: flex; align-items: center; justify-content: center;">
                        <svg width="16" height="16" fill="#9ca3af" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                `}
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 500; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ${highlightMatch(item.name, query)}
                    </div>
                    ${item.categories?.length ? `
                        <div style="font-size: 12px; color: #6b7280;">
                            ${item.categories.slice(0, 2).join(', ')}
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');

        dropdown.innerHTML = html;
        dropdown.style.display = 'block';

        // Eventos de click en items
        dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', function() {
                const value = this.dataset.value;
                input.value = value;
                hideAutocomplete();
                executeSearch(value);
            });
        });
    }

    /**
     * Ocultar dropdown de autocompletado.
     */
    function hideAutocomplete() {
        const dropdown = document.querySelector('.autocomplete-dropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
    }

    /**
     * Ejecutar búsqueda principal.
     */
    async function executeSearch(query) {
        query = query.trim();
        
        if (query.length < CONFIG.minChars && query.length > 0) {
            showNotification('Ingresa al menos 2 caracteres', 'warning');
            return;
        }

        // Cancelar request anterior si existe
        if (currentRequest) {
            currentRequest.abort();
        }

        // Mostrar loading
        showLoading(true);

        try {
            const controller = new AbortController();
            currentRequest = controller;

            const url = new URL(CONFIG.endpoints.search, window.location.origin);
            url.searchParams.set('q', query);
            
            // Agregar filtros activos si existen
            const categoryFilter = document.querySelector('[data-category-filter]');
            if (categoryFilter?.value) {
                url.searchParams.set('category_id', categoryFilter.value);
            }

            const response = await fetch(url, {
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Error en búsqueda');

            const result = await response.json();
            
            if (result.success) {
                renderSearchResults(result.data, query);
                updateResultsCount(result.total, query);
            } else {
                showNotification(result.message || 'Error en búsqueda', 'error');
            }

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('SearchModule: Error en búsqueda', error);
                showNotification('Error al realizar la búsqueda', 'error');
            }
        } finally {
            showLoading(false);
            currentRequest = null;
        }
    }

    /**
     * Renderizar resultados de búsqueda.
     */
    function renderSearchResults(results, query) {
        const container = document.querySelector('[data-search-results]') || 
                         document.querySelector('.design-grid');
        
        if (!container) {
            console.warn('SearchModule: No se encontró contenedor de resultados');
            return;
        }

        if (!results.length) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div style="color: #9ca3af;">
                        <svg width="48" height="48" fill="currentColor" viewBox="0 0 20 20" class="mx-auto mb-3">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                        </svg>
                        <p class="mb-1 font-weight-medium">No se encontraron resultados</p>
                        <p class="small">Intenta con otros términos de búsqueda</p>
                    </div>
                </div>
            `;
            return;
        }

        // Usar template existente o crear cards
        const html = results.map(design => renderDesignCard(design, query)).join('');
        container.innerHTML = html;

        // Re-inicializar eventos si es necesario
        if (typeof initDesignCards === 'function') {
            initDesignCards();
        }
    }

    /**
     * Renderizar card de diseño individual.
     */
    function renderDesignCard(design, query) {
        return `
            <div class="design-card" 
                 data-design-id="${design.id}"
                 data-name="${escapeHtml(design.name)}"
                 data-description="${escapeHtml(design.description || '')}"
                 data-variants="${design.variants_count || 0}">
                <div class="design-image">
                    ${design.image ? `
                        <img src="${design.image}" 
                             alt="${escapeHtml(design.name)}"
                             onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="no-image" style="display: none;">
                            <i class="fas fa-image fa-3x mb-2 text-gray-300"></i>
                            <span>Sin imagen</span>
                        </div>
                    ` : `
                        <div class="no-image">
                            <i class="fas fa-image fa-3x mb-2 text-gray-300"></i>
                            <span>Sin imagen</span>
                        </div>
                    `}
                </div>
                <div class="design-body text-center">
                    <h6 class="design-title">${highlightMatch(design.name, query)}</h6>
                    <div class="design-variants">
                        ${design.variants_count} variante${design.variants_count !== 1 ? 's' : ''}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Actualizar contador de resultados.
     */
    function updateResultsCount(count, query) {
        const counter = document.querySelector('[data-results-count]');
        if (counter) {
            counter.textContent = query 
                ? `${count} resultado${count !== 1 ? 's' : ''} para "${query}"`
                : `${count} diseño${count !== 1 ? 's' : ''}`;
        }
    }

    /**
     * Limpiar resultados (mostrar todos).
     */
    function clearResults() {
        // Recargar página sin parámetros de búsqueda
        const url = new URL(window.location);
        url.searchParams.delete('q');
        url.searchParams.delete('search');
        
        if (window.location.search) {
            window.location = url;
        }
    }

    /**
     * Mostrar/ocultar loading.
     */
    function showLoading(show) {
        const loader = document.querySelector('[data-search-loader]');
        const results = document.querySelector('[data-search-results], .design-grid');
        
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
        
        if (results) {
            results.style.opacity = show ? '0.5' : '1';
            results.style.pointerEvents = show ? 'none' : 'auto';
        }
    }

    /**
     * Mostrar notificación.
     */
    function showNotification(message, type = 'info') {
        // Usar SweetAlert si está disponible
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            console.log(`[${type}] ${message}`);
        }
    }

    /**
     * Resaltar coincidencias en texto.
     */
    function highlightMatch(text, query) {
        if (!query || !text) return text;
        
        const words = query.split(/\s+/).filter(w => w.length >= 2);
        let result = text;
        
        words.forEach(word => {
            const regex = new RegExp(`(${escapeRegex(word)})`, 'gi');
            result = result.replace(regex, '<mark style="background: #fef3c7; padding: 0 2px; border-radius: 2px;">$1</mark>');
        });
        
        return result;
    }

    /**
     * Escapar HTML para prevenir XSS.
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Escapar caracteres especiales de regex.
     */
    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // API pública
    return {
        init,
        search: executeSearch,
        clear: clearResults
    };
})();

// Auto-inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    SearchModule.init();
});
