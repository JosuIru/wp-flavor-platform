/**
 * Visual Builder Pro - Logic Integration
 * Integra el sistema de variables y lógica con el editor
 *
 * @package Flavor_Platform
 * @since 2.5.0
 */

(function() {
    'use strict';

    /**
     * VBP Logic Integration
     * Conecta el sistema de lógica con el resto del editor
     */
    var VBPLogicIntegration = {
        /**
         * Inicializado
         */
        _initialized: false,

        /**
         * Inicializar integración
         */
        init: function() {
            if (this._initialized) return;

            // Esperar a que Alpine esté listo
            if (typeof Alpine === 'undefined') {
                document.addEventListener('alpine:init', this._setup.bind(this));
            } else {
                this._setup();
            }

            this._initialized = true;
        },

        /**
         * Configurar integración
         */
        _setup: function() {
            var self = this;

            // Esperar a que el store de VBP esté disponible
            this._waitForStore(function() {
                // Inicializar sistema de variables si no está inicializado
                if (window.VBPVariables && typeof window.VBPVariables.init === 'function') {
                    // Ya se inicializa en alpine:init
                }

                // Registrar tab de lógica en el inspector
                self._registerInspectorTab();

                // Escuchar eventos del editor
                self._setupEventListeners();

                // Restaurar lógica de elementos cuando se carga el documento
                self._restoreElementLogic();

                console.log('[VBP Logic Integration] Initialized');
            });
        },

        /**
         * Esperar a que el store esté disponible
         */
        _waitForStore: function(callback) {
            var maxRetries = 50;
            var retries = 0;

            var checkStore = function() {
                if (typeof Alpine !== 'undefined' && Alpine.store && Alpine.store('vbp')) {
                    callback();
                } else if (retries < maxRetries) {
                    retries++;
                    setTimeout(checkStore, 100);
                } else {
                    console.warn('[VBP Logic Integration] Store not available after max retries');
                }
            };

            checkStore();
        },

        /**
         * Registrar tab de lógica en el inspector
         */
        _registerInspectorTab: function() {
            // Agregar tab de lógica al inspector
            document.addEventListener('vbp:inspector:init', function() {
                var inspectorTabs = document.querySelector('.vbp-inspector__tabs');
                if (!inspectorTabs) return;

                // Verificar si ya existe
                if (inspectorTabs.querySelector('[data-tab="logic"]')) return;

                // Crear tab de lógica
                var logicTab = document.createElement('button');
                logicTab.className = 'vbp-inspector__tab';
                logicTab.setAttribute('data-tab', 'logic');
                logicTab.setAttribute('title', 'Lógica y Variables');
                logicTab.innerHTML = '<span class="vbp-inspector__tab-icon">⚡</span>';

                inspectorTabs.appendChild(logicTab);
            });
        },

        /**
         * Configurar event listeners
         */
        _setupEventListeners: function() {
            var self = this;

            // Cuando se selecciona un elemento
            document.addEventListener('vbp:element:selected', function(event) {
                var elementId = event.detail.elementId;
                if (elementId) {
                    self._loadElementLogic(elementId);
                }
            });

            // Cuando se actualiza un elemento
            document.addEventListener('vbp:element:updated', function(event) {
                var elementId = event.detail.id;
                if (elementId) {
                    self._syncElementLogic(elementId);
                }
            });

            // Cuando se elimina un elemento
            document.addEventListener('vbp:element:removed', function(event) {
                var elementId = event.detail.id;
                if (elementId) {
                    self._cleanupElementLogic(elementId);
                }
            });

            // Cuando se guarda el documento
            document.addEventListener('vbp:beforeSave', function(event) {
                self._prepareLogicForSave();
            });

            // Cuando se carga el documento
            document.addEventListener('vbp:document:loaded', function(event) {
                self._restoreElementLogic();
            });

            // Abrir panel de variables
            document.addEventListener('vbp:logic:openPanel', function() {
                self._openVariablesPanel();
            });

            // Trigger para lazy load del bundle de lógica
            document.addEventListener('vbp:logic:required', function() {
                self._loadLogicBundle();
            });
        },

        /**
         * Cargar lógica de un elemento
         */
        _loadElementLogic: function(elementId) {
            var store = Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep ? store.getElementDeep(elementId) : store.getElement(elementId);
            if (!element || !element.logic) return;

            var logic = element.logic;

            // Restaurar bindings
            if (logic.bindings && window.VBPBindings) {
                for (var prop in logic.bindings) {
                    window.VBPBindings.bind(elementId, prop, logic.bindings[prop]);
                }
            }

            // Restaurar loop
            if (logic.loop && window.VBPLoops) {
                window.VBPLoops.configure(elementId, logic.loop);
            }

            // Restaurar eventos
            if (logic.events && window.VBPActions) {
                window.VBPActions.configure(elementId, logic.events);
            }

            // Restaurar estados
            if (logic.states && window.VBPComponentStates) {
                window.VBPComponentStates.configure(elementId, logic.states);
            }
        },

        /**
         * Sincronizar lógica de un elemento
         */
        _syncElementLogic: function(elementId) {
            // La lógica se sincroniza automáticamente mediante los sistemas individuales
        },

        /**
         * Limpiar lógica de un elemento eliminado
         */
        _cleanupElementLogic: function(elementId) {
            // Limpiar bindings
            if (window.VBPBindings) {
                var bindings = window.VBPBindings.getBindings(elementId);
                for (var prop in bindings) {
                    window.VBPBindings.unbind(elementId, prop);
                }
            }

            // Limpiar loop
            if (window.VBPLoops) {
                window.VBPLoops.remove(elementId);
            }

            // Limpiar eventos
            if (window.VBPActions) {
                window.VBPActions.remove(elementId);
            }

            // Limpiar estados
            // Los estados se eliminan con el elemento
        },

        /**
         * Preparar lógica para guardar
         */
        _prepareLogicForSave: function() {
            var store = Alpine.store('vbp');
            if (!store) return;

            // Guardar estado de variables
            if (window.VBPVariables) {
                var variableState = window.VBPVariables.exportState();

                // Guardar en settings del documento
                if (!store.settings) store.settings = {};
                store.settings.logic = {
                    variables: variableState.variables,
                    collections: variableState.collections
                };
            }

            // La lógica de elementos ya se guarda en element.logic
        },

        /**
         * Restaurar lógica de todos los elementos
         */
        _restoreElementLogic: function() {
            var store = Alpine.store('vbp');
            if (!store || !store.elements) return;

            var self = this;

            // Restaurar variables del documento
            if (store.settings && store.settings.logic && window.VBPVariables) {
                window.VBPVariables.importState(store.settings.logic);
            }

            // Restaurar lógica de cada elemento
            var processElement = function(element) {
                if (element.logic) {
                    self._loadElementLogic(element.id);
                }

                // Procesar hijos
                if (element.children && element.children.length > 0) {
                    element.children.forEach(processElement);
                }
            };

            store.elements.forEach(processElement);
        },

        /**
         * Abrir panel de variables
         */
        _openVariablesPanel: function() {
            // Disparar evento para que el panel responda
            document.dispatchEvent(new CustomEvent('vbp:variablesPanel:open'));

            // Cargar bundle de lógica si no está cargado
            this._loadLogicBundle();
        },

        /**
         * Cargar bundle de lógica (lazy loading)
         */
        _loadLogicBundle: function() {
            if (window.VBPAssetLoader && typeof window.VBPAssetLoader.cargarBundle === 'function') {
                window.VBPAssetLoader.cargarBundle('vbp-logic');
            }

            // Disparar trigger para el sistema de lazy loading
            document.dispatchEvent(new CustomEvent('vbp:trigger', {
                detail: { trigger: 'logic-panel-open' }
            }));
        },

        /**
         * Exportar lógica de página completa para frontend
         */
        exportForFrontend: function() {
            var store = Alpine.store('vbp');
            if (!store) return null;

            var exportData = {
                variables: {},
                bindings: {},
                loops: {},
                events: {},
                states: {}
            };

            // Exportar variables (solo las no-wp y no-readonly)
            if (window.VBPVariables) {
                var allVars = window.VBPVariables.listVariables();
                allVars.forEach(function(variable) {
                    if (!variable.readOnly && variable.id.indexOf('wp:') !== 0) {
                        exportData.variables[variable.id] = {
                            name: variable.name,
                            type: variable.type,
                            value: variable.value,
                            scope: variable.scope
                        };
                    }
                });
            }

            // Exportar lógica de cada elemento
            var processElement = function(element) {
                if (element.logic) {
                    if (element.logic.bindings) {
                        exportData.bindings[element.id] = element.logic.bindings;
                    }
                    if (element.logic.loop) {
                        exportData.loops[element.id] = element.logic.loop;
                    }
                    if (element.logic.events) {
                        exportData.events[element.id] = element.logic.events;
                    }
                    if (element.logic.states) {
                        exportData.states[element.id] = element.logic.states;
                    }
                }

                // Procesar hijos
                if (element.children && element.children.length > 0) {
                    element.children.forEach(processElement);
                }
            };

            if (store.elements) {
                store.elements.forEach(processElement);
            }

            return exportData;
        },

        /**
         * Generar script de configuración para frontend
         */
        generateFrontendConfig: function() {
            var exportData = this.exportForFrontend();
            if (!exportData) return '';

            var script = '<script type="application/json" id="vbp-logic-config">';
            script += JSON.stringify(exportData);
            script += '</script>';

            return script;
        },

        /**
         * Verificar si hay lógica que exportar
         */
        hasLogicToExport: function() {
            var exportData = this.exportForFrontend();
            if (!exportData) return false;

            return Object.keys(exportData.variables).length > 0 ||
                   Object.keys(exportData.bindings).length > 0 ||
                   Object.keys(exportData.loops).length > 0 ||
                   Object.keys(exportData.events).length > 0;
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            VBPLogicIntegration.init();
        });
    } else {
        VBPLogicIntegration.init();
    }

    // Exportar a window
    window.VBPLogicIntegration = VBPLogicIntegration;

})();
