/**
 * Visual Builder Pro - Instance Inspector
 * Inspector especializado para instancias de símbolos
 *
 * @package Flavor_Platform
 * @since 2.1.0
 */

// Fallback de vbpLog si no está definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

(function() {
    'use strict';

    /**
     * Inspector de Instancias de Símbolos
     */
    window.VBPInstanceInspector = {
        /**
         * Panel del inspector actualmente montado
         */
        panel: null,

        /**
         * ID del elemento de instancia actualmente inspeccionado
         */
        currentInstanceId: null,

        /**
         * Datos cacheados de la instancia actual
         */
        cachedInstanceData: null,

        /**
         * Inicializar el inspector de instancias
         */
        init: function() {
            var self = this;

            // Hook al evento de selección del store
            document.addEventListener('vbp:selection-changed', function(evento) {
                self.handleSelectionChanged(evento);
            });

            // Escuchar cambios en el store de símbolos
            document.addEventListener('vbp:symbol:updated', function(evento) {
                if (self.currentInstanceId) {
                    self.refreshInspector();
                }
            });

            // Escuchar actualizaciones de instancias
            document.addEventListener('vbp:instance:updated', function(evento) {
                if (evento.detail && evento.detail.instanceId === self.currentInstanceId) {
                    self.refreshInspector();
                }
            });

            vbpLog.log('InstanceInspector: inicializado');
        },

        /**
         * Manejar cambio de selección
         * @param {CustomEvent} evento - Evento de selección
         */
        handleSelectionChanged: function(evento) {
            var elementoIds = evento.detail && evento.detail.elementIds;

            if (!elementoIds || elementoIds.length !== 1) {
                this.ocultarInspectorInstancia();
                return;
            }

            var elementoId = elementoIds[0];

            // Verificar si es una instancia de símbolo
            if (this.esInstanciaDeSymbol(elementoId)) {
                this.mostrarInspectorInstancia(elementoId);
            } else {
                this.ocultarInspectorInstancia();
            }
        },

        /**
         * Verificar si un elemento es una instancia de símbolo
         * @param {string} elementoId - ID del elemento
         * @returns {boolean}
         */
        esInstanciaDeSymbol: function(elementoId) {
            // Verificar usando el store de símbolos si está disponible
            if (window.VBPSymbols && typeof window.VBPSymbols.esInstancia === 'function') {
                return window.VBPSymbols.esInstancia(elementoId);
            }

            // Fallback: verificar directamente en el store VBP
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return false;

            var elemento = store.getElementDeep ? store.getElementDeep(elementoId) : store.getElement(elementoId);
            return elemento && elemento.type === '__symbol_instance__';
        },

        /**
         * Obtener datos de la instancia
         * @param {string} instanceId - ID de la instancia
         * @returns {object|null}
         */
        obtenerDatosInstancia: function(instanceId) {
            // Usar el store de símbolos si está disponible
            if (window.VBPSymbols && typeof window.VBPSymbols.obtenerDatosInstancia === 'function') {
                return window.VBPSymbols.obtenerDatosInstancia(instanceId);
            }

            // Fallback: obtener directamente del store
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return null;

            var elemento = store.getElementDeep ? store.getElementDeep(instanceId) : store.getElement(instanceId);
            if (!elemento || elemento.type !== '__symbol_instance__') return null;

            return {
                instanceId: instanceId,
                symbolId: elemento.symbolId || elemento.data.symbolId,
                overrides: elemento.overrides || elemento.data.overrides || {},
                variant: elemento.variant || elemento.data.variant || 'default',
                version: elemento.symbolVersion || elemento.data.symbolVersion || 1
            };
        },

        /**
         * Obtener información del símbolo maestro
         * @param {string} symbolId - ID del símbolo
         * @returns {object|null}
         */
        obtenerInfoSymbol: function(symbolId) {
            if (window.VBPSymbols && typeof window.VBPSymbols.obtenerSymbol === 'function') {
                return window.VBPSymbols.obtenerSymbol(symbolId);
            }

            // Fallback: intentar desde el store de símbolos de Alpine
            var symbolsStore = window.Alpine && Alpine.store('vbpSymbols');
            if (symbolsStore && symbolsStore.symbols) {
                return symbolsStore.symbols.find(function(simbolo) {
                    return simbolo.id === symbolId || simbolo.post_id === parseInt(symbolId);
                });
            }

            return null;
        },

        /**
         * Mostrar el inspector de instancia
         * @param {string} instanceId - ID de la instancia
         */
        mostrarInspectorInstancia: function(instanceId) {
            this.currentInstanceId = instanceId;
            this.cachedInstanceData = this.obtenerDatosInstancia(instanceId);

            if (!this.cachedInstanceData) {
                vbpLog.warn('InstanceInspector: No se pudieron obtener datos de la instancia', instanceId);
                return;
            }

            var infoSymbol = this.obtenerInfoSymbol(this.cachedInstanceData.symbolId);

            // Renderizar el panel del inspector
            this.renderPanel(this.cachedInstanceData, infoSymbol);

            // Emitir evento
            document.dispatchEvent(new CustomEvent('vbp:instance-inspector:shown', {
                detail: {
                    instanceId: instanceId,
                    symbolId: this.cachedInstanceData.symbolId
                }
            }));

            vbpLog.log('InstanceInspector: mostrando inspector para instancia', instanceId);
        },

        /**
         * Ocultar el inspector de instancia
         */
        ocultarInspectorInstancia: function() {
            if (this.currentInstanceId) {
                document.dispatchEvent(new CustomEvent('vbp:instance-inspector:hidden', {
                    detail: { instanceId: this.currentInstanceId }
                }));
            }

            this.currentInstanceId = null;
            this.cachedInstanceData = null;

            // Remover el panel si existe
            if (this.panel) {
                this.panel.remove();
                this.panel = null;
            }

            // Mostrar el inspector normal si está oculto
            var inspectorNormal = document.querySelector('.vbp-inspector-content');
            if (inspectorNormal) {
                inspectorNormal.style.display = '';
            }
        },

        /**
         * Refrescar el inspector con datos actualizados
         */
        refreshInspector: function() {
            if (this.currentInstanceId) {
                this.mostrarInspectorInstancia(this.currentInstanceId);
            }
        },

        /**
         * Renderizar el panel del inspector
         * @param {object} datosInstancia - Datos de la instancia
         * @param {object} infoSymbol - Información del símbolo
         */
        renderPanel: function(datosInstancia, infoSymbol) {
            var self = this;
            var overrides = datosInstancia.overrides || {};
            var tieneOverrides = Object.keys(overrides).length > 0;
            var symbolName = infoSymbol ? (infoSymbol.title || infoSymbol.name || 'Símbolo') : 'Símbolo desconocido';
            var symbolDescription = infoSymbol ? (infoSymbol.description || '') : '';
            var exposedProperties = infoSymbol && infoSymbol.exposedProperties ? infoSymbol.exposedProperties : [];
            var variants = infoSymbol && infoSymbol.variants ? infoSymbol.variants : {};
            var currentVariant = datosInstancia.variant || infoSymbol.default_variant || 'default';

            // Asegurar que siempre exista la variante default
            if (!variants.default) {
                variants.default = { name: 'Por defecto', overrides: {} };
            }

            // Ocultar el inspector normal
            var inspectorNormal = document.querySelector('.vbp-inspector-content');
            if (inspectorNormal) {
                inspectorNormal.style.display = 'none';
            }

            // Crear o actualizar el panel
            if (!this.panel) {
                this.panel = document.createElement('div');
                this.panel.className = 'vbp-instance-inspector';

                // Insertar después del inspector normal
                var sidebar = document.querySelector('.vbp-sidebar-right .vbp-panel-content');
                if (sidebar) {
                    sidebar.appendChild(this.panel);
                }
            }

            // Verificar si hay actualización pendiente
            var hasPendingUpdate = this.checkPendingUpdate(datosInstancia, infoSymbol);

            // Generar HTML del panel
            var htmlContent = this.generatePanelHTML({
                symbolName: symbolName,
                symbolDescription: symbolDescription,
                symbolId: datosInstancia.symbolId,
                instanceId: datosInstancia.instanceId,
                tieneOverrides: tieneOverrides,
                overrides: overrides,
                exposedProperties: exposedProperties,
                hasPendingUpdate: hasPendingUpdate,
                variants: variants,
                currentVariant: currentVariant
            });

            this.panel.innerHTML = htmlContent;

            // Vincular eventos
            this.bindPanelEvents();
        },

        /**
         * Generar HTML del panel
         * @param {object} opciones - Opciones para renderizar
         * @returns {string}
         */
        generatePanelHTML: function(opciones) {
            var htmlParts = [];

            // Header
            htmlParts.push('<div class="vbp-instance-header">');
            htmlParts.push('  <div class="vbp-instance-badge">');
            htmlParts.push('    <span class="vbp-instance-icon" aria-hidden="true">&#9671;</span>');
            htmlParts.push('    <span class="vbp-instance-label">Instancia de Símbolo</span>');
            htmlParts.push('  </div>');
            htmlParts.push('  <h3 class="vbp-instance-title">' + this.escapeHtml(opciones.symbolName) + '</h3>');

            if (opciones.symbolDescription) {
                htmlParts.push('  <p class="vbp-instance-description">' + this.escapeHtml(opciones.symbolDescription) + '</p>');
            }

            htmlParts.push('</div>');

            // Selector de variantes
            var variantKeys = Object.keys(opciones.variants || {});
            if (variantKeys.length > 1) {
                htmlParts.push('<div class="vbp-instance-variant-section">');
                htmlParts.push('  <div class="vbp-section-header">');
                htmlParts.push('    <h4>Variante</h4>');
                htmlParts.push('  </div>');
                htmlParts.push('  <div class="vbp-variant-selector-container">');
                htmlParts.push('    <select class="vbp-variant-select" data-action="change-variant">');

                for (var i = 0; i < variantKeys.length; i++) {
                    var variantKey = variantKeys[i];
                    var variant = opciones.variants[variantKey];
                    var variantName = variant.name || variantKey;
                    var isSelected = variantKey === opciones.currentVariant ? ' selected' : '';
                    htmlParts.push('      <option value="' + this.escapeHtml(variantKey) + '"' + isSelected + '>' + this.escapeHtml(variantName) + '</option>');
                }

                htmlParts.push('    </select>');
                htmlParts.push('    <button type="button" class="vbp-btn-create-variant" data-action="create-variant" title="Crear nueva variante desde el estado actual">');
                htmlParts.push('      <span aria-hidden="true">+</span>');
                htmlParts.push('    </button>');
                htmlParts.push('  </div>');
                if (opciones.tieneOverrides) {
                    htmlParts.push('  <p class="vbp-variant-hint">Tienes cambios locales. Puedes guardarlos como nueva variante.</p>');
                }
                htmlParts.push('</div>');
            } else if (opciones.tieneOverrides) {
                // Solo mostrar opcion de crear variante si hay overrides
                htmlParts.push('<div class="vbp-instance-variant-section vbp-variant-create-only">');
                htmlParts.push('  <button type="button" class="vbp-btn vbp-btn-outline vbp-btn-sm" data-action="create-variant">');
                htmlParts.push('    <span aria-hidden="true">+</span> Guardar como variante');
                htmlParts.push('  </button>');
                htmlParts.push('</div>');
            }

            // Indicador de actualización pendiente
            if (opciones.hasPendingUpdate) {
                htmlParts.push('<div class="vbp-instance-update-notice">');
                htmlParts.push('  <span class="vbp-update-icon" aria-hidden="true">&#8635;</span>');
                htmlParts.push('  <span>El símbolo maestro ha sido actualizado</span>');
                htmlParts.push('  <button type="button" class="vbp-btn-sync" data-action="sync" title="Sincronizar con el símbolo maestro">');
                htmlParts.push('    Sincronizar');
                htmlParts.push('  </button>');
                htmlParts.push('</div>');
            }

            // Propiedades expuestas
            htmlParts.push('<div class="vbp-instance-properties">');
            htmlParts.push('  <div class="vbp-section-header">');
            htmlParts.push('    <h4>Propiedades</h4>');
            if (opciones.tieneOverrides) {
                htmlParts.push('    <button type="button" class="vbp-btn-text vbp-btn-reset-all" data-action="reset-all" title="Restablecer todos los valores">');
                htmlParts.push('      Restablecer todo');
                htmlParts.push('    </button>');
            }
            htmlParts.push('  </div>');

            if (opciones.exposedProperties.length > 0) {
                htmlParts.push('  <div class="vbp-property-list">');
                for (var i = 0; i < opciones.exposedProperties.length; i++) {
                    var propiedad = opciones.exposedProperties[i];
                    var propPath = propiedad.path || propiedad.id;
                    var hasOverride = opciones.overrides.hasOwnProperty(propPath);
                    var valorActual = hasOverride ? opciones.overrides[propPath] : (propiedad.defaultValue || '');

                    htmlParts.push(this.renderPropertyRow(propiedad, valorActual, hasOverride, propPath));
                }
                htmlParts.push('  </div>');
            } else {
                htmlParts.push('  <p class="vbp-no-properties">Este símbolo no tiene propiedades expuestas.</p>');
            }

            htmlParts.push('</div>');

            // Acciones
            htmlParts.push('<div class="vbp-instance-actions">');
            htmlParts.push('  <button type="button" class="vbp-btn vbp-btn-secondary vbp-btn-go-to-master" data-action="go-to-master" data-symbol-id="' + opciones.symbolId + '">');
            htmlParts.push('    <span aria-hidden="true">&#8599;</span> Editar símbolo');
            htmlParts.push('  </button>');
            htmlParts.push('  <button type="button" class="vbp-btn vbp-btn-outline vbp-btn-swap" data-action="swap-symbol" data-instance-id="' + opciones.instanceId + '" title="Cambiar a otro símbolo">');
            htmlParts.push('    <span aria-hidden="true">&#8644;</span> Cambiar símbolo');
            htmlParts.push('  </button>');
            htmlParts.push('  <button type="button" class="vbp-btn vbp-btn-outline vbp-btn-detach" data-action="detach" title="Separar instancia del símbolo maestro">');
            htmlParts.push('    <span aria-hidden="true">&#10006;</span> Separar');
            htmlParts.push('  </button>');
            htmlParts.push('</div>');

            return htmlParts.join('\n');
        },

        /**
         * Renderizar una fila de propiedad
         * @param {object} propiedad - Definición de la propiedad
         * @param {*} valor - Valor actual
         * @param {boolean} hasOverride - Si tiene override
         * @param {string} propPath - Ruta de la propiedad
         * @returns {string}
         */
        renderPropertyRow: function(propiedad, valor, hasOverride, propPath) {
            var nombrePropiedad = propiedad.label || propiedad.name || propPath;
            var tipoPropiedad = propiedad.type || 'text';
            var partes = [];

            partes.push('<div class="vbp-property-row' + (hasOverride ? ' has-override' : '') + '" data-prop-path="' + this.escapeHtml(propPath) + '">');
            partes.push('  <div class="vbp-property-label">');

            if (hasOverride) {
                partes.push('    <span class="vbp-override-indicator" title="Valor modificado">&#9679;</span>');
            }

            partes.push('    <span class="vbp-property-name">' + this.escapeHtml(nombrePropiedad) + '</span>');
            partes.push('  </div>');
            partes.push('  <div class="vbp-property-control">');

            // Renderizar control según tipo
            switch (tipoPropiedad) {
                case 'color':
                    partes.push(this.renderColorInput(propPath, valor));
                    break;
                case 'select':
                    partes.push(this.renderSelectInput(propPath, valor, propiedad.options || []));
                    break;
                case 'boolean':
                case 'checkbox':
                    partes.push(this.renderCheckboxInput(propPath, valor));
                    break;
                case 'number':
                    partes.push(this.renderNumberInput(propPath, valor, propiedad));
                    break;
                case 'textarea':
                    partes.push(this.renderTextareaInput(propPath, valor));
                    break;
                case 'image':
                case 'url':
                    partes.push(this.renderUrlInput(propPath, valor, tipoPropiedad));
                    break;
                default:
                    partes.push(this.renderTextInput(propPath, valor));
            }

            partes.push('  </div>');

            // Botón de reset para propiedades con override
            if (hasOverride) {
                partes.push('  <button type="button" class="vbp-btn-reset-override" data-action="reset-override" data-prop-path="' + this.escapeHtml(propPath) + '" title="Restablecer valor original">');
                partes.push('    <span aria-hidden="true">&#8634;</span>');
                partes.push('  </button>');
            }

            partes.push('</div>');

            return partes.join('\n');
        },

        /**
         * Renderizar input de texto
         */
        renderTextInput: function(propPath, valor) {
            return '<input type="text" class="vbp-property-input" data-prop-path="' +
                this.escapeHtml(propPath) + '" value="' + this.escapeHtml(valor || '') + '" />';
        },

        /**
         * Renderizar input de número
         */
        renderNumberInput: function(propPath, valor, propiedad) {
            var minAttr = propiedad.min !== undefined ? ' min="' + propiedad.min + '"' : '';
            var maxAttr = propiedad.max !== undefined ? ' max="' + propiedad.max + '"' : '';
            var stepAttr = propiedad.step !== undefined ? ' step="' + propiedad.step + '"' : '';

            return '<input type="number" class="vbp-property-input" data-prop-path="' +
                this.escapeHtml(propPath) + '" value="' + this.escapeHtml(valor || '') + '"' +
                minAttr + maxAttr + stepAttr + ' />';
        },

        /**
         * Renderizar input de color
         */
        renderColorInput: function(propPath, valor) {
            var colorValue = this.normalizeColorForInput(valor);
            return '<div class="vbp-color-input-wrapper">' +
                '<input type="color" class="vbp-property-input vbp-color-input" data-prop-path="' +
                this.escapeHtml(propPath) + '" value="' + colorValue + '" />' +
                '<input type="text" class="vbp-color-text" value="' + this.escapeHtml(valor || '') + '" readonly />' +
                '</div>';
        },

        /**
         * Renderizar select
         */
        renderSelectInput: function(propPath, valor, opciones) {
            var partes = ['<select class="vbp-property-input vbp-property-select" data-prop-path="' + this.escapeHtml(propPath) + '">'];

            for (var i = 0; i < opciones.length; i++) {
                var opcion = opciones[i];
                var optValue = typeof opcion === 'object' ? opcion.value : opcion;
                var optLabel = typeof opcion === 'object' ? opcion.label : opcion;
                var selected = valor === optValue ? ' selected' : '';
                partes.push('<option value="' + this.escapeHtml(optValue) + '"' + selected + '>' + this.escapeHtml(optLabel) + '</option>');
            }

            partes.push('</select>');
            return partes.join('');
        },

        /**
         * Renderizar checkbox
         */
        renderCheckboxInput: function(propPath, valor) {
            var checked = valor === true || valor === 'true' || valor === 1 ? ' checked' : '';
            return '<label class="vbp-checkbox-wrapper">' +
                '<input type="checkbox" class="vbp-property-input vbp-property-checkbox" data-prop-path="' +
                this.escapeHtml(propPath) + '"' + checked + ' />' +
                '<span class="vbp-checkbox-mark"></span>' +
                '</label>';
        },

        /**
         * Renderizar textarea
         */
        renderTextareaInput: function(propPath, valor) {
            return '<textarea class="vbp-property-input vbp-property-textarea" data-prop-path="' +
                this.escapeHtml(propPath) + '" rows="3">' + this.escapeHtml(valor || '') + '</textarea>';
        },

        /**
         * Renderizar input de URL/imagen
         */
        renderUrlInput: function(propPath, valor, tipo) {
            var partes = [
                '<div class="vbp-url-input-wrapper">',
                '<input type="text" class="vbp-property-input vbp-url-input" data-prop-path="' +
                this.escapeHtml(propPath) + '" value="' + this.escapeHtml(valor || '') + '" placeholder="' +
                (tipo === 'image' ? 'URL de imagen' : 'URL') + '" />'
            ];

            if (tipo === 'image') {
                partes.push('<button type="button" class="vbp-btn-media" data-action="select-media" data-prop-path="' +
                    this.escapeHtml(propPath) + '" title="Seleccionar imagen"><span aria-hidden="true">&#128247;</span></button>');
            }

            partes.push('</div>');
            return partes.join('');
        },

        /**
         * Vincular eventos del panel
         */
        bindPanelEvents: function() {
            var self = this;

            if (!this.panel) return;

            // Delegación de eventos para inputs
            this.panel.addEventListener('input', function(evento) {
                var input = evento.target;
                if (!input.classList.contains('vbp-property-input')) return;

                var propPath = input.dataset.propPath;
                var valor = input.type === 'checkbox' ? input.checked : input.value;

                self.handleOverrideChange(self.currentInstanceId, propPath, valor);
            });

            // Delegación de eventos para selector de variante
            this.panel.addEventListener('change', function(evento) {
                var select = evento.target;
                if (select.classList.contains('vbp-variant-select')) {
                    self.handleVariantChange(self.currentInstanceId, select.value);
                }
            });

            // Delegación de eventos para botones
            this.panel.addEventListener('click', function(evento) {
                var boton = evento.target.closest('[data-action]');
                if (!boton) return;

                var accion = boton.dataset.action;

                switch (accion) {
                    case 'go-to-master':
                        self.handleGoToMaster(boton.dataset.symbolId);
                        break;
                    case 'detach':
                        self.handleDetach(self.currentInstanceId);
                        break;
                    case 'reset-override':
                        self.handleResetOverride(self.currentInstanceId, boton.dataset.propPath);
                        break;
                    case 'reset-all':
                        self.handleResetAll(self.currentInstanceId);
                        break;
                    case 'sync':
                        self.handleSync(self.currentInstanceId);
                        break;
                    case 'select-media':
                        self.handleSelectMedia(boton.dataset.propPath);
                        break;
                    case 'create-variant':
                        self.handleCreateVariant(self.currentInstanceId);
                        break;
                    case 'swap-symbol':
                        self.handleSwapSymbol(boton.dataset.instanceId);
                        break;
                }
            });
        },

        /**
         * Manejar apertura del modal de swap
         * @param {string} instanceId - ID de la instancia
         */
        handleSwapSymbol: function(instanceId) {
            vbpLog.log('InstanceInspector: abriendo modal swap para', instanceId);

            // Usar VBPSwapModal si está disponible
            if (window.VBPSwapModal && typeof window.VBPSwapModal.abrir === 'function') {
                window.VBPSwapModal.abrir(instanceId);
                return;
            }

            // Alternativa: emitir evento para que el modal lo capture
            document.dispatchEvent(new CustomEvent('vbp:swap-modal:open', {
                detail: { elementId: instanceId }
            }));
        },

        /**
         * Manejar cambio de variante
         * @param {string} instanceId - ID de la instancia
         * @param {string} variantKey - Nueva variante seleccionada
         */
        handleVariantChange: function(instanceId, variantKey) {
            vbpLog.log('InstanceInspector: cambiando variante a', variantKey);

            if (window.VBPSymbols && typeof window.VBPSymbols.cambiarVariante === 'function') {
                window.VBPSymbols.cambiarVariante(instanceId, variantKey)
                    .then(function() {
                        // Refrescar inspector
                        if (typeof VBPToast !== 'undefined') {
                            VBPToast.success('Variante aplicada');
                        }
                    })
                    .catch(function(error) {
                        vbpLog.error('InstanceInspector: Error cambiando variante', error);
                        if (typeof VBPToast !== 'undefined') {
                            VBPToast.error('Error al cambiar variante');
                        }
                    });
            } else {
                // Fallback: actualizar directamente en el store
                var store = window.Alpine && Alpine.store('vbp');
                if (store) {
                    var elemento = store.getElementDeep ? store.getElementDeep(instanceId) : store.getElement(instanceId);
                    if (elemento) {
                        elemento.variant = variantKey;
                        store.markAsDirty && store.markAsDirty();

                        document.dispatchEvent(new CustomEvent('vbp:instance:variant-changed', {
                            detail: {
                                instanceId: instanceId,
                                variant: variantKey
                            }
                        }));
                    }
                }
            }
        },

        /**
         * Manejar creación de nueva variante desde instancia
         * @param {string} instanceId - ID de la instancia
         */
        handleCreateVariant: function(instanceId) {
            var variantName = prompt('Nombre de la nueva variante:', '');

            if (!variantName || variantName.trim() === '') {
                return;
            }

            vbpLog.log('InstanceInspector: creando variante', variantName);

            if (window.VBPSymbols && typeof window.VBPSymbols.crearVarianteDesdeInstancia === 'function') {
                var self = this;
                window.VBPSymbols.crearVarianteDesdeInstancia(instanceId, variantName.trim())
                    .then(function(data) {
                        // Refrescar inspector para mostrar nueva variante
                        self.refreshInspector();

                        if (typeof VBPToast !== 'undefined') {
                            VBPToast.success('Variante "' + variantName + '" creada');
                        }
                    })
                    .catch(function(error) {
                        vbpLog.error('InstanceInspector: Error creando variante', error);
                        if (typeof VBPToast !== 'undefined') {
                            VBPToast.error('Error al crear variante: ' + error.message);
                        }
                    });
            } else {
                vbpLog.warn('InstanceInspector: VBPSymbols.crearVarianteDesdeInstancia no disponible');
            }
        },

        /**
         * Manejar cambio de override
         * @param {string} instanceId - ID de la instancia
         * @param {string} propPath - Ruta de la propiedad
         * @param {*} valor - Nuevo valor
         */
        handleOverrideChange: function(instanceId, propPath, valor) {
            vbpLog.log('InstanceInspector: override cambiado', propPath, valor);

            if (window.VBPSymbols && typeof window.VBPSymbols.setOverride === 'function') {
                window.VBPSymbols.setOverride(instanceId, propPath, valor);
            } else {
                // Fallback: actualizar directamente en el store
                var store = window.Alpine && Alpine.store('vbp');
                if (store) {
                    var elemento = store.getElementDeep ? store.getElementDeep(instanceId) : store.getElement(instanceId);
                    if (elemento) {
                        var overrides = JSON.parse(JSON.stringify(elemento.overrides || elemento.data.overrides || {}));
                        overrides[propPath] = valor;

                        if (elemento.overrides !== undefined) {
                            store.updateElement(instanceId, { overrides: overrides });
                        } else {
                            var data = JSON.parse(JSON.stringify(elemento.data || {}));
                            data.overrides = overrides;
                            store.updateElement(instanceId, { data: data });
                        }
                    }
                }
            }

            // Emitir evento
            document.dispatchEvent(new CustomEvent('vbp:instance:override-changed', {
                detail: {
                    instanceId: instanceId,
                    propPath: propPath,
                    value: valor
                }
            }));

            // Actualizar indicador visual
            var fila = this.panel.querySelector('[data-prop-path="' + propPath + '"]');
            if (fila && !fila.classList.contains('has-override')) {
                fila.classList.add('has-override');
                // Añadir indicador
                var labelContainer = fila.querySelector('.vbp-property-label');
                if (labelContainer && !labelContainer.querySelector('.vbp-override-indicator')) {
                    var indicator = document.createElement('span');
                    indicator.className = 'vbp-override-indicator';
                    indicator.title = 'Valor modificado';
                    indicator.innerHTML = '&#9679;';
                    labelContainer.insertBefore(indicator, labelContainer.firstChild);
                }
            }
        },

        /**
         * Manejar reset de override individual
         * @param {string} instanceId - ID de la instancia
         * @param {string} propPath - Ruta de la propiedad
         */
        handleResetOverride: function(instanceId, propPath) {
            vbpLog.log('InstanceInspector: reseteando override', propPath);

            if (window.VBPSymbols && typeof window.VBPSymbols.removeOverride === 'function') {
                window.VBPSymbols.removeOverride(instanceId, propPath);
            } else {
                // Fallback
                var store = window.Alpine && Alpine.store('vbp');
                if (store) {
                    var elemento = store.getElementDeep ? store.getElementDeep(instanceId) : store.getElement(instanceId);
                    if (elemento) {
                        var overrides = JSON.parse(JSON.stringify(elemento.overrides || elemento.data.overrides || {}));
                        delete overrides[propPath];

                        if (elemento.overrides !== undefined) {
                            store.updateElement(instanceId, { overrides: overrides });
                        } else {
                            var data = JSON.parse(JSON.stringify(elemento.data || {}));
                            data.overrides = overrides;
                            store.updateElement(instanceId, { data: data });
                        }
                    }
                }
            }

            // Refrescar inspector
            this.refreshInspector();

            // Emitir evento
            document.dispatchEvent(new CustomEvent('vbp:instance:override-reset', {
                detail: {
                    instanceId: instanceId,
                    propPath: propPath
                }
            }));
        },

        /**
         * Manejar reset de todos los overrides
         * @param {string} instanceId - ID de la instancia
         */
        handleResetAll: function(instanceId) {
            if (!confirm('¿Restablecer todos los valores a los originales del símbolo?')) {
                return;
            }

            vbpLog.log('InstanceInspector: reseteando todos los overrides');

            if (window.VBPSymbols && typeof window.VBPSymbols.resetAllOverrides === 'function') {
                window.VBPSymbols.resetAllOverrides(instanceId);
            } else {
                // Fallback
                var store = window.Alpine && Alpine.store('vbp');
                if (store) {
                    var elemento = store.getElementDeep ? store.getElementDeep(instanceId) : store.getElement(instanceId);
                    if (elemento) {
                        if (elemento.overrides !== undefined) {
                            store.updateElement(instanceId, { overrides: {} });
                        } else {
                            var data = JSON.parse(JSON.stringify(elemento.data || {}));
                            data.overrides = {};
                            store.updateElement(instanceId, { data: data });
                        }
                    }
                }
            }

            // Refrescar inspector
            this.refreshInspector();

            // Emitir evento
            document.dispatchEvent(new CustomEvent('vbp:instance:all-overrides-reset', {
                detail: { instanceId: instanceId }
            }));

            // Notificación
            if (typeof VBPToast !== 'undefined') {
                VBPToast.success('Valores restablecidos');
            }
        },

        /**
         * Manejar detach (separar de símbolo)
         * @param {string} instanceId - ID de la instancia
         */
        handleDetach: function(instanceId) {
            if (!confirm('¿Separar esta instancia del símbolo maestro? Se convertirá en bloques independientes.')) {
                return;
            }

            vbpLog.log('InstanceInspector: separando instancia', instanceId);

            if (window.VBPSymbols && typeof window.VBPSymbols.detachInstance === 'function') {
                var resultado = window.VBPSymbols.detachInstance(instanceId);

                if (resultado) {
                    // Ocultar inspector de instancia
                    this.ocultarInspectorInstancia();

                    // Notificación
                    if (typeof VBPToast !== 'undefined') {
                        VBPToast.success('Instancia separada correctamente');
                    }

                    // Emitir evento
                    document.dispatchEvent(new CustomEvent('vbp:instance:detached', {
                        detail: {
                            instanceId: instanceId,
                            newElements: resultado.elements
                        }
                    }));
                }
            } else {
                vbpLog.warn('InstanceInspector: VBPSymbols.detachInstance no disponible');
            }
        },

        /**
         * Manejar ir al símbolo maestro
         * @param {string} symbolId - ID del símbolo
         */
        handleGoToMaster: function(symbolId) {
            vbpLog.log('InstanceInspector: navegando al símbolo', symbolId);

            if (window.VBPSymbols && typeof window.VBPSymbols.openSymbolEditor === 'function') {
                window.VBPSymbols.openSymbolEditor(symbolId);
            } else {
                // Fallback: navegar a la URL de edición del símbolo
                var editUrl = (typeof VBP_Config !== 'undefined' && VBP_Config.adminUrl)
                    ? VBP_Config.adminUrl + 'post.php?post=' + symbolId + '&action=edit'
                    : '/wp-admin/post.php?post=' + symbolId + '&action=edit';

                // Abrir en nueva pestaña o modal según configuración
                window.open(editUrl, '_blank');
            }

            // Emitir evento
            document.dispatchEvent(new CustomEvent('vbp:symbol:open-editor', {
                detail: { symbolId: symbolId }
            }));
        },

        /**
         * Manejar sincronización con símbolo actualizado
         * @param {string} instanceId - ID de la instancia
         */
        handleSync: function(instanceId) {
            vbpLog.log('InstanceInspector: sincronizando instancia', instanceId);

            if (window.VBPSymbols && typeof window.VBPSymbols.syncInstance === 'function') {
                window.VBPSymbols.syncInstance(instanceId);

                // Refrescar inspector
                this.refreshInspector();

                // Notificación
                if (typeof VBPToast !== 'undefined') {
                    VBPToast.success('Instancia sincronizada');
                }

                // Emitir evento
                document.dispatchEvent(new CustomEvent('vbp:instance:synced', {
                    detail: { instanceId: instanceId }
                }));
            }
        },

        /**
         * Manejar selección de media
         * @param {string} propPath - Ruta de la propiedad
         */
        handleSelectMedia: function(propPath) {
            var self = this;

            if (typeof wp !== 'undefined' && wp.media) {
                var frame = wp.media({
                    title: 'Seleccionar imagen',
                    button: { text: 'Usar esta imagen' },
                    multiple: false,
                    library: { type: 'image' }
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    if (attachment && attachment.url) {
                        self.handleOverrideChange(self.currentInstanceId, propPath, attachment.url);

                        // Actualizar input
                        var input = self.panel.querySelector('[data-prop-path="' + propPath + '"]');
                        if (input) {
                            input.value = attachment.url;
                        }
                    }
                });

                frame.open();
            } else {
                var url = prompt('URL de la imagen:');
                if (url) {
                    this.handleOverrideChange(this.currentInstanceId, propPath, url);
                }
            }
        },

        /**
         * Verificar si hay actualización pendiente del símbolo
         * @param {object} datosInstancia - Datos de la instancia
         * @param {object} infoSymbol - Info del símbolo
         * @returns {boolean}
         */
        checkPendingUpdate: function(datosInstancia, infoSymbol) {
            if (!datosInstancia || !infoSymbol) return false;

            var instanceVersion = datosInstancia.version || 1;
            var symbolVersion = infoSymbol.version || infoSymbol.revision || 1;

            return symbolVersion > instanceVersion;
        },

        /**
         * Normalizar color para input type="color"
         * @param {string} color - Color a normalizar
         * @returns {string}
         */
        normalizeColorForInput: function(color) {
            if (!color || typeof color !== 'string') {
                return '#000000';
            }

            color = color.trim();
            if (!color) {
                return '#000000';
            }

            if (!color.startsWith('#')) {
                color = '#' + color;
            }

            var hexRegex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
            if (hexRegex.test(color)) {
                if (color.length === 4) {
                    var r = color[1], g = color[2], b = color[3];
                    return '#' + r + r + g + g + b + b;
                }
                return color;
            }

            return '#000000';
        },

        /**
         * Escapar HTML
         * @param {string} str - String a escapar
         * @returns {string}
         */
        escapeHtml: function(str) {
            if (typeof str !== 'string') {
                str = String(str || '');
            }
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.VBPInstanceInspector.init();
        });
    } else {
        window.VBPInstanceInspector.init();
    }
})();
