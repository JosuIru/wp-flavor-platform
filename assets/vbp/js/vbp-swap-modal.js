/**
 * Visual Builder Pro - Modal de Swap de Simbolo
 *
 * Modal para cambiar la instancia de simbolo a usar otro simbolo diferente.
 * Muestra sugerencias, compatibilidad de overrides y preview del nuevo simbolo.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.23
 */

(function() {
    'use strict';

    /**
     * Modal de Swap de Simbolo
     */
    window.VBPSwapModal = {
        /** Estado del modal */
        visible: false,

        /** ID del elemento instancia */
        elementId: null,

        /** ID del simbolo actual */
        currentSymbolId: null,

        /** Nombre del simbolo actual */
        currentSymbolName: '',

        /** Lista de todos los simbolos disponibles */
        symbols: [],

        /** Sugerencias de swap ordenadas por compatibilidad */
        suggestions: [],

        /** Busqueda de usuario */
        searchQuery: '',

        /** Simbolo seleccionado para swap */
        selectedSymbolId: null,

        /** Datos de compatibilidad del simbolo seleccionado */
        compatibility: null,

        /** Estado de carga */
        loading: false,

        /** Tab activo: 'suggestions' o 'all' */
        activeTab: 'suggestions',

        /** Overrides actuales de la instancia */
        currentOverrides: {},

        /**
         * Inicializar el modal
         */
        init: function() {
            var self = this;

            // Escuchar eventos de apertura
            document.addEventListener('vbp:swap-modal:open', function(evento) {
                if (evento.detail && evento.detail.elementId) {
                    self.abrir(evento.detail.elementId);
                }
            });

            // Cerrar con Escape
            document.addEventListener('keydown', function(evento) {
                if (evento.key === 'Escape' && self.visible) {
                    self.cerrar();
                }
            });

            this.crearHTML();
        },

        /**
         * Crear el HTML del modal
         */
        crearHTML: function() {
            if (document.getElementById('vbp-swap-modal')) {
                return;
            }

            var modalHTML = '<div id="vbp-swap-modal" class="vbp-swap-modal" style="display:none;">' +
                '<div class="vbp-swap-modal-overlay"></div>' +
                '<div class="vbp-swap-modal-container">' +
                    '<div class="vbp-swap-modal-header">' +
                        '<h3 class="vbp-swap-modal-title">Cambiar Simbolo</h3>' +
                        '<button type="button" class="vbp-swap-modal-close" aria-label="Cerrar">' +
                            '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">' +
                                '<path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>' +
                            '</svg>' +
                        '</button>' +
                    '</div>' +
                    '<div class="vbp-swap-modal-current">' +
                        '<span class="vbp-swap-modal-label">Simbolo actual:</span>' +
                        '<span class="vbp-swap-modal-current-name"></span>' +
                    '</div>' +
                    '<div class="vbp-swap-modal-tabs">' +
                        '<button type="button" class="vbp-swap-tab active" data-tab="suggestions">Sugerencias</button>' +
                        '<button type="button" class="vbp-swap-tab" data-tab="all">Todos</button>' +
                    '</div>' +
                    '<div class="vbp-swap-modal-search">' +
                        '<input type="text" class="vbp-swap-search-input" placeholder="Buscar simbolo..." />' +
                    '</div>' +
                    '<div class="vbp-swap-modal-body">' +
                        '<div class="vbp-swap-symbols-list"></div>' +
                        '<div class="vbp-swap-loading" style="display:none;">' +
                            '<div class="vbp-swap-spinner"></div>' +
                            '<span>Cargando...</span>' +
                        '</div>' +
                        '<div class="vbp-swap-empty" style="display:none;">' +
                            '<span>No se encontraron simbolos</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="vbp-swap-compatibility-panel" style="display:none;">' +
                        '<div class="vbp-swap-compatibility-header">' +
                            '<span class="vbp-swap-compat-title">Compatibilidad</span>' +
                            '<span class="vbp-swap-compat-score"></span>' +
                        '</div>' +
                        '<div class="vbp-swap-compatibility-details"></div>' +
                    '</div>' +
                    '<div class="vbp-swap-modal-footer">' +
                        '<button type="button" class="vbp-swap-btn vbp-swap-btn-secondary vbp-swap-btn-cancel">Cancelar</button>' +
                        '<button type="button" class="vbp-swap-btn vbp-swap-btn-primary vbp-swap-btn-confirm" disabled>Cambiar Simbolo</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

            document.body.insertAdjacentHTML('beforeend', modalHTML);
            this.bindEvents();
        },

        /**
         * Vincular eventos del modal
         */
        bindEvents: function() {
            var self = this;
            var modalElement = document.getElementById('vbp-swap-modal');

            if (!modalElement) return;

            // Cerrar con overlay
            var overlayElement = modalElement.querySelector('.vbp-swap-modal-overlay');
            overlayElement.addEventListener('click', function() {
                self.cerrar();
            });

            // Boton cerrar
            var closeButton = modalElement.querySelector('.vbp-swap-modal-close');
            closeButton.addEventListener('click', function() {
                self.cerrar();
            });

            // Boton cancelar
            var cancelButton = modalElement.querySelector('.vbp-swap-btn-cancel');
            cancelButton.addEventListener('click', function() {
                self.cerrar();
            });

            // Boton confirmar
            var confirmButton = modalElement.querySelector('.vbp-swap-btn-confirm');
            confirmButton.addEventListener('click', function() {
                self.confirmarSwap();
            });

            // Tabs
            var tabButtons = modalElement.querySelectorAll('.vbp-swap-tab');
            tabButtons.forEach(function(tabButton) {
                tabButton.addEventListener('click', function() {
                    var tabName = this.getAttribute('data-tab');
                    self.cambiarTab(tabName);
                });
            });

            // Busqueda
            var searchInput = modalElement.querySelector('.vbp-swap-search-input');
            searchInput.addEventListener('input', function() {
                self.searchQuery = this.value;
                self.filtrarSimbolos();
            });
        },

        /**
         * Abrir modal para una instancia
         * @param {string} elementId - ID del elemento instancia
         */
        abrir: function(elementId) {
            var self = this;
            var store = Alpine.store('vbp');

            if (!store) {
                console.warn('[VBPSwapModal] Store VBP no disponible');
                return;
            }

            var element = store.getElementById(elementId);

            if (!element || !VBPSymbols.esInstancia(elementId)) {
                console.warn('[VBPSwapModal] Elemento no es una instancia');
                return;
            }

            this.elementId = elementId;
            this.currentSymbolId = element.symbolId;
            this.currentSymbolName = element.symbolName || element.name || 'Simbolo';
            this.currentOverrides = element.overrides || {};
            this.selectedSymbolId = null;
            this.compatibility = null;
            this.searchQuery = '';
            this.activeTab = 'suggestions';

            // Mostrar modal
            var modalElement = document.getElementById('vbp-swap-modal');
            modalElement.style.display = 'flex';
            this.visible = true;

            // Actualizar UI
            modalElement.querySelector('.vbp-swap-modal-current-name').textContent = this.currentSymbolName;
            modalElement.querySelector('.vbp-swap-search-input').value = '';
            modalElement.querySelector('.vbp-swap-btn-confirm').disabled = true;

            // Activar tab sugerencias
            var tabsSuggestions = modalElement.querySelectorAll('.vbp-swap-tab');
            tabsSuggestions.forEach(function(tabElement) {
                tabElement.classList.toggle('active', tabElement.getAttribute('data-tab') === 'suggestions');
            });

            // Cargar datos
            this.cargarDatos();
        },

        /**
         * Cerrar modal
         */
        cerrar: function() {
            var modalElement = document.getElementById('vbp-swap-modal');
            if (modalElement) {
                modalElement.style.display = 'none';
            }

            this.visible = false;
            this.elementId = null;
            this.currentSymbolId = null;
            this.selectedSymbolId = null;
            this.compatibility = null;

            // Ocultar panel de compatibilidad
            var compatPanel = modalElement.querySelector('.vbp-swap-compatibility-panel');
            if (compatPanel) {
                compatPanel.style.display = 'none';
            }
        },

        /**
         * Cargar simbolos y sugerencias
         */
        cargarDatos: function() {
            var self = this;
            this.loading = true;
            this.mostrarLoading(true);

            // Cargar sugerencias y todos los simbolos en paralelo
            Promise.all([
                VBPSymbols.obtenerSugerenciasSwap(this.elementId),
                this.cargarTodosSimbolos()
            ])
            .then(function(results) {
                self.suggestions = results[0] || [];
                self.symbols = results[1] || [];
                self.loading = false;
                self.mostrarLoading(false);
                self.renderizarSimbolos();
            })
            .catch(function(error) {
                console.error('[VBPSwapModal] Error cargando datos:', error);
                self.loading = false;
                self.mostrarLoading(false);
            });
        },

        /**
         * Cargar todos los simbolos disponibles
         */
        cargarTodosSimbolos: function() {
            var self = this;

            // Usar cache si existe
            if (VBPSymbols.symbols && VBPSymbols.symbols.length > 0) {
                return Promise.resolve(
                    VBPSymbols.symbols.filter(function(simbolo) {
                        return simbolo.id !== self.currentSymbolId;
                    })
                );
            }

            // Cargar desde API
            return fetch(VBP_Config.restUrl + 'symbols', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': VBP_Config.restNonce
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.symbols) {
                    return data.symbols.filter(function(simbolo) {
                        return simbolo.id !== self.currentSymbolId;
                    });
                }
                return [];
            });
        },

        /**
         * Mostrar/ocultar loading
         */
        mostrarLoading: function(mostrar) {
            var modalElement = document.getElementById('vbp-swap-modal');
            var loadingElement = modalElement.querySelector('.vbp-swap-loading');
            var listElement = modalElement.querySelector('.vbp-swap-symbols-list');

            loadingElement.style.display = mostrar ? 'flex' : 'none';
            listElement.style.display = mostrar ? 'none' : 'block';
        },

        /**
         * Cambiar tab activo
         */
        cambiarTab: function(tabName) {
            this.activeTab = tabName;

            var modalElement = document.getElementById('vbp-swap-modal');
            var tabButtons = modalElement.querySelectorAll('.vbp-swap-tab');
            tabButtons.forEach(function(tabButton) {
                tabButton.classList.toggle('active', tabButton.getAttribute('data-tab') === tabName);
            });

            this.renderizarSimbolos();
        },

        /**
         * Filtrar simbolos por busqueda
         */
        filtrarSimbolos: function() {
            this.renderizarSimbolos();
        },

        /**
         * Renderizar lista de simbolos
         */
        renderizarSimbolos: function() {
            var self = this;
            var modalElement = document.getElementById('vbp-swap-modal');
            var listContainer = modalElement.querySelector('.vbp-swap-symbols-list');
            var emptyElement = modalElement.querySelector('.vbp-swap-empty');

            // Determinar que lista mostrar
            var simbolosAmostrar = this.activeTab === 'suggestions' ? this.suggestions : this.symbols;

            // Filtrar por busqueda
            var queryLower = this.searchQuery.toLowerCase().trim();
            if (queryLower) {
                simbolosAmostrar = simbolosAmostrar.filter(function(simbolo) {
                    var nombreLower = (simbolo.name || '').toLowerCase();
                    var categoriaLower = (simbolo.category || '').toLowerCase();
                    return nombreLower.indexOf(queryLower) !== -1 || categoriaLower.indexOf(queryLower) !== -1;
                });
            }

            // Mostrar empty state si no hay resultados
            if (simbolosAmostrar.length === 0) {
                listContainer.innerHTML = '';
                emptyElement.style.display = 'flex';
                return;
            }

            emptyElement.style.display = 'none';

            // Generar HTML
            var htmlContent = '';
            simbolosAmostrar.forEach(function(simbolo) {
                var isSelected = simbolo.id === self.selectedSymbolId;
                var compatScore = simbolo.compatibility_score !== undefined ? simbolo.compatibility_score : null;
                var similarityScore = simbolo.similarity_score || 0;

                htmlContent += '<div class="vbp-swap-symbol-item' + (isSelected ? ' selected' : '') + '" data-symbol-id="' + simbolo.id + '">' +
                    '<div class="vbp-swap-symbol-icon">' +
                        '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                            '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>' +
                        '</svg>' +
                    '</div>' +
                    '<div class="vbp-swap-symbol-info">' +
                        '<div class="vbp-swap-symbol-name">' + self.escapeHtml(simbolo.name) + '</div>' +
                        '<div class="vbp-swap-symbol-meta">';

                if (simbolo.category) {
                    htmlContent += '<span class="vbp-swap-symbol-category">' + self.escapeHtml(simbolo.category) + '</span>';
                }

                if (compatScore !== null) {
                    var compatClass = compatScore >= 80 ? 'high' : (compatScore >= 50 ? 'medium' : 'low');
                    htmlContent += '<span class="vbp-swap-symbol-compat ' + compatClass + '">' + Math.round(compatScore) + '% compatible</span>';
                } else if (similarityScore > 0) {
                    htmlContent += '<span class="vbp-swap-symbol-similarity">Similitud: ' + similarityScore + '</span>';
                }

                htmlContent += '</div>' +
                    '</div>' +
                    '<div class="vbp-swap-symbol-check">' +
                        '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">' +
                            '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>' +
                        '</svg>' +
                    '</div>' +
                '</div>';
            });

            listContainer.innerHTML = htmlContent;

            // Vincular eventos de seleccion
            var itemElements = listContainer.querySelectorAll('.vbp-swap-symbol-item');
            itemElements.forEach(function(itemElement) {
                itemElement.addEventListener('click', function() {
                    var symbolId = parseInt(this.getAttribute('data-symbol-id'), 10);
                    self.seleccionarSimbolo(symbolId);
                });
            });
        },

        /**
         * Seleccionar un simbolo
         */
        seleccionarSimbolo: function(symbolId) {
            var self = this;
            var modalElement = document.getElementById('vbp-swap-modal');

            // Actualizar seleccion visual
            var itemElements = modalElement.querySelectorAll('.vbp-swap-symbol-item');
            itemElements.forEach(function(itemElement) {
                var itemSymbolId = parseInt(itemElement.getAttribute('data-symbol-id'), 10);
                itemElement.classList.toggle('selected', itemSymbolId === symbolId);
            });

            this.selectedSymbolId = symbolId;

            // Habilitar boton confirmar
            modalElement.querySelector('.vbp-swap-btn-confirm').disabled = false;

            // Verificar compatibilidad
            this.verificarCompatibilidad(symbolId);
        },

        /**
         * Verificar compatibilidad con el simbolo seleccionado
         */
        verificarCompatibilidad: function(symbolId) {
            var self = this;
            var modalElement = document.getElementById('vbp-swap-modal');
            var compatPanel = modalElement.querySelector('.vbp-swap-compatibility-panel');

            // Si no hay overrides, no mostrar panel
            if (Object.keys(this.currentOverrides).length === 0) {
                compatPanel.style.display = 'none';
                this.compatibility = { compatibility_score: 100, compatible: {}, incompatible: [] };
                return;
            }

            // Mostrar panel con loading
            compatPanel.style.display = 'block';
            compatPanel.querySelector('.vbp-swap-compat-score').textContent = 'Verificando...';
            compatPanel.querySelector('.vbp-swap-compatibility-details').innerHTML = '';

            VBPSymbols.verificarCompatibilidadSwap(this.elementId, symbolId)
                .then(function(compatibilityData) {
                    self.compatibility = compatibilityData;
                    self.mostrarCompatibilidad(compatibilityData);
                })
                .catch(function(error) {
                    console.error('[VBPSwapModal] Error verificando compatibilidad:', error);
                    compatPanel.style.display = 'none';
                });
        },

        /**
         * Mostrar detalles de compatibilidad
         */
        mostrarCompatibilidad: function(compatibilityData) {
            var modalElement = document.getElementById('vbp-swap-modal');
            var compatPanel = modalElement.querySelector('.vbp-swap-compatibility-panel');
            var scoreElement = compatPanel.querySelector('.vbp-swap-compat-score');
            var detailsElement = compatPanel.querySelector('.vbp-swap-compatibility-details');

            var scoreValue = Math.round(compatibilityData.compatibility_score);
            var scoreClass = scoreValue >= 80 ? 'high' : (scoreValue >= 50 ? 'medium' : 'low');

            scoreElement.innerHTML = '<span class="vbp-swap-score-value ' + scoreClass + '">' + scoreValue + '%</span>';

            var detailsHTML = '';

            // Mostrar compatibles
            var compatibleCount = compatibilityData.compatible_count || Object.keys(compatibilityData.compatible || {}).length;
            if (compatibleCount > 0) {
                detailsHTML += '<div class="vbp-swap-compat-item compatible">' +
                    '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">' +
                        '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>' +
                    '</svg>' +
                    '<span>' + compatibleCount + ' propiedades se preservaran</span>' +
                '</div>';
            }

            // Mostrar incompatibles
            var incompatibleCount = compatibilityData.incompatible_count || (compatibilityData.incompatible || []).length;
            if (incompatibleCount > 0) {
                detailsHTML += '<div class="vbp-swap-compat-item incompatible">' +
                    '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">' +
                        '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>' +
                    '</svg>' +
                    '<span>' + incompatibleCount + ' propiedades se descartaran</span>' +
                '</div>';
            }

            detailsElement.innerHTML = detailsHTML;
        },

        /**
         * Confirmar el swap
         */
        confirmarSwap: function() {
            var self = this;

            if (!this.selectedSymbolId) {
                return;
            }

            var modalElement = document.getElementById('vbp-swap-modal');
            var confirmButton = modalElement.querySelector('.vbp-swap-btn-confirm');
            confirmButton.disabled = true;
            confirmButton.textContent = 'Cambiando...';

            VBPSymbols.swapInstancia(this.elementId, this.selectedSymbolId, true)
                .then(function(resultado) {
                    self.cerrar();
                })
                .catch(function(error) {
                    console.error('[VBPSwapModal] Error en swap:', error);
                    confirmButton.disabled = false;
                    confirmButton.textContent = 'Cambiar Simbolo';
                    VBPSymbols.mostrarError('Error al cambiar simbolo: ' + error.message);
                });
        },

        /**
         * Escapar HTML
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // =============================================
    // Inicializacion
    // =============================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                VBPSwapModal.init();
            }, 200);
        });
    } else {
        setTimeout(function() {
            VBPSwapModal.init();
        }, 200);
    }

})();
