/**
 * Visual Builder Pro - Instance Renderer
 * Renderizado de instancias de símbolos en el canvas
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
     * Renderizador de Instancias de Símbolos
     */
    window.VBPInstanceRenderer = {
        /**
         * Cache de contenidos renderizados
         * Clave: instanceId, Valor: { html: string, version: number }
         */
        renderCache: new Map(),

        /**
         * Observador de mutaciones para detectar cambios
         */
        mutationObserver: null,

        /**
         * Timeout para debounce de re-renderizado
         */
        renderDebounceTimers: {},

        /**
         * Flag para prevenir inicialización duplicada
         */
        _initialized: false,

        /**
         * Referencias a event handlers para cleanup
         */
        _eventHandlers: {},

        /**
         * Escapar HTML para prevenir XSS
         * @param {string} text - Texto a escapar
         * @returns {string} Texto escapado
         */
        escapeHtml: function(text) {
            if (typeof text !== 'string') return String(text);
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Inicializar el renderer
         */
        init: function() {
            // Prevenir inicialización duplicada
            if (this._initialized) {
                vbpLog.log('InstanceRenderer: ya inicializado, ignorando');
                return;
            }
            this._initialized = true;

            var self = this;

            // Registrar renderer custom para '__symbol_instance__' en el canvas
            this.registerCustomRenderer();

            // Crear handlers con referencias para poder limpiarlos después
            this._eventHandlers.symbolUpdated = function(evento) {
                self.handleSymbolUpdated(evento);
            };
            this._eventHandlers.instanceUpdated = function(evento) {
                self.handleInstanceUpdated(evento);
            };
            this._eventHandlers.overrideChanged = function(evento) {
                self.handleOverrideChanged(evento);
            };
            this._eventHandlers.instanceDetached = function(evento) {
                var detail = evento.detail || {};
                self.invalidateCache(detail.instanceId || detail.elementId);
            };

            // Escuchar eventos
            document.addEventListener('vbp:symbol:updated', this._eventHandlers.symbolUpdated);
            document.addEventListener('vbp:instance:updated', this._eventHandlers.instanceUpdated);
            document.addEventListener('vbp:instance:override-changed', this._eventHandlers.overrideChanged);
            document.addEventListener('vbp:instance:detached', this._eventHandlers.instanceDetached);

            // Inicializar observador de mutaciones
            this.initMutationObserver();

            vbpLog.log('InstanceRenderer: inicializado');
        },

        /**
         * Registrar el renderer custom para instancias de símbolos
         */
        registerCustomRenderer: function() {
            var self = this;

            // Verificar si VBPCanvas tiene el método para registrar renderers
            if (window.VBPCanvas && typeof window.VBPCanvas.registerCustomRenderer === 'function') {
                window.VBPCanvas.registerCustomRenderer('__symbol_instance__', function(elemento, contenedor) {
                    return self.renderInstance(elemento, contenedor);
                });
                vbpLog.log('InstanceRenderer: renderer registrado en VBPCanvas');
            } else {
                // Fallback: usar hook de evento
                document.addEventListener('vbp:render-element', function(evento) {
                    var elemento = evento.detail.element;
                    var contenedor = evento.detail.container;

                    if (elemento && elemento.type === '__symbol_instance__') {
                        evento.preventDefault();
                        var wrapper = self.renderInstance(elemento, contenedor);
                        if (evento.detail.callback) {
                            evento.detail.callback(wrapper);
                        }
                    }
                });
                vbpLog.log('InstanceRenderer: usando hook de evento como fallback');
            }
        },

        /**
         * Renderizar una instancia de símbolo
         * @param {object} elemento - Datos del elemento instancia
         * @param {HTMLElement} contenedor - Contenedor donde renderizar
         * @returns {HTMLElement} - Wrapper del elemento renderizado
         */
        renderInstance: function(elemento, contenedor) {
            var self = this;
            var instanceId = elemento.id;
            var symbolId = elemento.symbolId || (elemento.data && elemento.data.symbolId);
            var overrides = elemento.overrides || (elemento.data && elemento.data.overrides) || {};
            var hasOverrides = Object.keys(overrides).length > 0;

            vbpLog.log('InstanceRenderer: renderizando instancia', instanceId, 'de símbolo', symbolId);

            // Crear wrapper con indicadores
            var wrapper = document.createElement('div');
            wrapper.className = 'vbp-element vbp-symbol-instance';
            wrapper.dataset.elementId = instanceId;
            wrapper.dataset.vbpId = instanceId;
            wrapper.dataset.symbolId = symbolId || '';
            wrapper.dataset.hasOverrides = hasOverrides ? 'true' : 'false';
            wrapper.dataset.elementType = '__symbol_instance__';

            // Atributos de accesibilidad
            wrapper.setAttribute('role', 'region');
            wrapper.setAttribute('aria-label', 'Instancia de símbolo');
            wrapper.tabIndex = 0;

            // Badge de instancia
            var badge = this.createInstanceBadge(hasOverrides);
            wrapper.appendChild(badge);

            // Contenedor interno para el contenido
            var contenidoWrapper = document.createElement('div');
            contenidoWrapper.className = 'vbp-instance-content';
            wrapper.appendChild(contenidoWrapper);

            // Obtener contenido resuelto del símbolo
            var contenidoResuelto = this.resolverContenido(instanceId, elemento);

            if (contenidoResuelto && Array.isArray(contenidoResuelto) && contenidoResuelto.length > 0) {
                // Renderizar cada bloque del contenido
                this.renderBloques(contenidoResuelto, contenidoWrapper);
            } else {
                // Mostrar placeholder si no hay contenido
                contenidoWrapper.innerHTML = this.createPlaceholderHTML(symbolId);
            }

            // Añadir event listeners
            this.attachEventListeners(wrapper, instanceId, symbolId);

            // Añadir al contenedor si se proporcionó
            if (contenedor) {
                contenedor.appendChild(wrapper);
            }

            // Guardar en cache
            this.renderCache.set(instanceId, {
                wrapper: wrapper,
                version: elemento.symbolVersion || (elemento.data && elemento.data.symbolVersion) || 1,
                symbolId: symbolId
            });

            return wrapper;
        },

        /**
         * Crear badge de instancia
         * @param {boolean} hasOverrides - Si tiene overrides
         * @returns {HTMLElement}
         */
        createInstanceBadge: function(hasOverrides) {
            var badge = document.createElement('div');
            badge.className = 'vbp-instance-badge' + (hasOverrides ? ' has-overrides' : '');
            badge.innerHTML = '<span class="vbp-badge-icon" aria-hidden="true">&#9671;</span>';
            badge.title = hasOverrides ? 'Instancia de Símbolo (modificada)' : 'Instancia de Símbolo';
            badge.setAttribute('role', 'img');
            badge.setAttribute('aria-label', badge.title);
            return badge;
        },

        /**
         * Crear HTML de placeholder
         * @param {string} symbolId - ID del símbolo
         * @returns {string}
         */
        createPlaceholderHTML: function(symbolId) {
            return '<div class="vbp-instance-placeholder">' +
                '<span class="vbp-placeholder-icon" aria-hidden="true">&#9671;</span>' +
                '<span class="vbp-placeholder-text">Cargando símbolo' + (symbolId ? ' #' + symbolId : '') + '...</span>' +
                '</div>';
        },

        /**
         * Resolver contenido de la instancia aplicando overrides
         * @param {string} instanceId - ID de la instancia
         * @param {object} elemento - Datos del elemento
         * @returns {Array|null}
         */
        resolverContenido: function(instanceId, elemento) {
            // Usar VBPSymbols si está disponible
            if (window.VBPSymbols && typeof window.VBPSymbols.resolverContenido === 'function') {
                return window.VBPSymbols.resolverContenido(instanceId);
            }

            // Fallback: resolver manualmente
            var symbolId = elemento.symbolId || (elemento.data && elemento.data.symbolId);
            var overrides = elemento.overrides || (elemento.data && elemento.data.overrides) || {};

            // Obtener símbolo maestro
            var symbolMaestro = this.obtenerSymbolMaestro(symbolId);
            if (!symbolMaestro) {
                vbpLog.warn('InstanceRenderer: símbolo maestro no encontrado', symbolId);
                return null;
            }

            // Obtener contenido base del símbolo
            var contenidoBase = symbolMaestro.content || symbolMaestro.elements || symbolMaestro.blocks || [];
            if (!Array.isArray(contenidoBase)) {
                contenidoBase = [];
            }

            // Clonar contenido para aplicar overrides
            var contenidoClonado = JSON.parse(JSON.stringify(contenidoBase));

            // Aplicar overrides
            if (Object.keys(overrides).length > 0) {
                this.aplicarOverrides(contenidoClonado, overrides);
            }

            return contenidoClonado;
        },

        /**
         * Obtener símbolo maestro
         * @param {string} symbolId - ID del símbolo
         * @returns {object|null}
         */
        obtenerSymbolMaestro: function(symbolId) {
            if (window.VBPSymbols) {
                if (typeof window.VBPSymbols.obtenerSimbolo === 'function') {
                    return window.VBPSymbols.obtenerSimbolo(symbolId);
                }
                if (typeof window.VBPSymbols.obtenerSymbol === 'function') {
                    return window.VBPSymbols.obtenerSymbol(symbolId);
                }
            }

            // Fallback: intentar desde store de Alpine
            var symbolsStore = window.Alpine && Alpine.store('vbpSymbols');
            if (symbolsStore && symbolsStore.symbols) {
                return symbolsStore.symbols.find(function(s) {
                    return s.id === symbolId || s.post_id === parseInt(symbolId);
                });
            }

            return null;
        },

        /**
         * Aplicar overrides al contenido
         * @param {Array} contenido - Array de bloques
         * @param {object} overrides - Mapa de overrides
         */
        aplicarOverrides: function(contenido, overrides) {
            var self = this;

            for (var path in overrides) {
                if (!overrides.hasOwnProperty(path)) continue;

                var valor = overrides[path];
                this.setValueByPath(contenido, path, valor);
            }
        },

        /**
         * Establecer valor por path en estructura anidada
         * @param {Array|object} obj - Objeto/array destino
         * @param {string} path - Path tipo "0.data.titulo" o "1.children.0.data.texto"
         * @param {*} valor - Valor a establecer
         */
        setValueByPath: function(obj, path, valor) {
            var partes = path.split('.');
            var actual = obj;

            for (var i = 0; i < partes.length - 1; i++) {
                var parte = partes[i];
                var indice = parseInt(parte);

                if (!isNaN(indice) && Array.isArray(actual)) {
                    if (!actual[indice]) {
                        actual[indice] = {};
                    }
                    actual = actual[indice];
                } else if (actual && typeof actual === 'object') {
                    if (!actual[parte]) {
                        actual[parte] = {};
                    }
                    actual = actual[parte];
                } else {
                    return; // Path inválido
                }
            }

            var ultimaParte = partes[partes.length - 1];
            var indiceUltimo = parseInt(ultimaParte);

            if (!isNaN(indiceUltimo) && Array.isArray(actual)) {
                actual[indiceUltimo] = valor;
            } else if (actual && typeof actual === 'object') {
                actual[ultimaParte] = valor;
            }
        },

        /**
         * Renderizar bloques en un contenedor
         * @param {Array} bloques - Array de bloques a renderizar
         * @param {HTMLElement} contenedor - Contenedor destino
         */
        renderBloques: function(bloques, contenedor) {
            var self = this;

            // Usar el renderizador del canvas si está disponible
            if (window.vbpCanvasUtils && typeof window.vbpCanvasUtils.renderElement === 'function') {
                bloques.forEach(function(bloque) {
                    window.vbpCanvasUtils.renderElement(bloque, contenedor);
                });
            } else if (window.VBPCanvas && typeof window.VBPCanvas.renderElement === 'function') {
                bloques.forEach(function(bloque) {
                    window.VBPCanvas.renderElement(bloque, contenedor);
                });
            } else {
                // Fallback: renderizado básico
                bloques.forEach(function(bloque) {
                    var elementoHTML = self.renderBloqueBasico(bloque);
                    if (elementoHTML) {
                        contenedor.appendChild(elementoHTML);
                    }
                });
            }
        },

        /**
         * Renderizado básico de un bloque (fallback)
         * @param {object} bloque - Datos del bloque
         * @returns {HTMLElement|null}
         */
        renderBloqueBasico: function(bloque) {
            if (!bloque || !bloque.type) return null;

            var div = document.createElement('div');
            div.className = 'vbp-element vbp-element-' + bloque.type;
            div.dataset.elementId = bloque.id || '';
            div.dataset.elementType = bloque.type;

            // Renderizar contenido básico según tipo
            switch (bloque.type) {
                case 'heading':
                    var nivel = (bloque.data && bloque.data.level) || 2;
                    var texto = (bloque.data && bloque.data.text) || '';
                    var h = document.createElement('h' + nivel);
                    h.textContent = texto;
                    div.appendChild(h);
                    break;

                case 'text':
                    var p = document.createElement('p');
                    p.textContent = (bloque.data && (bloque.data.content || bloque.data.text)) || '';
                    div.appendChild(p);
                    break;

                case 'image':
                    var img = document.createElement('img');
                    img.src = (bloque.data && bloque.data.src) || '';
                    img.alt = (bloque.data && bloque.data.alt) || '';
                    div.appendChild(img);
                    break;

                case 'button':
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = (bloque.data && bloque.data.text) || 'Botón';
                    div.appendChild(btn);
                    break;

                case 'code-component':
                    // Intentar renderizar Code Component usando VBPCodeComponents
                    var componentData = bloque.data || {};
                    var componentId = componentData.componentId || componentData.id;
                    var componentProps = componentData.props || {};

                    if (componentId && window.VBPCodeComponents && typeof window.VBPCodeComponents.previewComponent === 'function') {
                        // Crear contenedor para el iframe de preview
                        var previewContainer = document.createElement('div');
                        previewContainer.className = 'vbp-code-component-preview';
                        previewContainer.dataset.componentId = componentId;
                        div.appendChild(previewContainer);

                        // previewComponent devuelve un iframe; algunos callers legacy esperan promesa
                        try {
                            var previewResult = window.VBPCodeComponents.previewComponent(componentId, componentProps);

                            if (previewResult && typeof previewResult.then === 'function') {
                                previewResult.then(function(previewHtml) {
                                    if (previewHtml && previewContainer.isConnected) {
                                        previewContainer.innerHTML = previewHtml;
                                    }
                                }).catch(function() {
                                    previewContainer.innerHTML = '<div class="vbp-block-placeholder">Code Component</div>';
                                });
                            } else if (previewResult && previewResult.nodeType === 1 && previewContainer.isConnected) {
                                previewContainer.innerHTML = '';
                                previewContainer.appendChild(previewResult);
                            } else {
                                previewContainer.innerHTML = '<div class="vbp-block-placeholder">Code Component</div>';
                            }
                        } catch (previewError) {
                            previewContainer.innerHTML = '<div class="vbp-block-placeholder">Code Component</div>';
                        }
                    } else {
                        // Fallback si VBPCodeComponents no está disponible
                        var placeholder = document.createElement('div');
                        placeholder.className = 'vbp-block-placeholder vbp-code-component-placeholder';
                        placeholder.innerHTML = '<span class="vbp-placeholder-icon">⚡</span>' +
                            '<span class="vbp-placeholder-text">Code Component' +
                            (componentData.name ? ': ' + this.escapeHtml(componentData.name) : '') +
                            '</span>';
                        div.appendChild(placeholder);
                    }
                    break;

                case 'module':
                case 'shortcode':
                    // Renderizar placeholder para módulos/shortcodes
                    var moduleData = bloque.data || {};
                    var modulePlaceholder = document.createElement('div');
                    modulePlaceholder.className = 'vbp-block-placeholder vbp-module-placeholder';
                    modulePlaceholder.innerHTML = '<span class="vbp-placeholder-icon">📦</span>' +
                        '<span class="vbp-placeholder-text">' +
                        this.escapeHtml(moduleData.shortcode || moduleData.module || 'Module') +
                        '</span>';
                    div.appendChild(modulePlaceholder);
                    break;

                default:
                    div.innerHTML = '<div class="vbp-block-placeholder">' + this.escapeHtml(bloque.type) + '</div>';
            }

            // Renderizar hijos si existen
            if (bloque.children && Array.isArray(bloque.children) && bloque.children.length > 0) {
                var childrenContainer = document.createElement('div');
                childrenContainer.className = 'vbp-element-children';
                this.renderBloques(bloque.children, childrenContainer);
                div.appendChild(childrenContainer);
            }

            return div;
        },

        /**
         * Adjuntar event listeners al wrapper
         * @param {HTMLElement} wrapper - Wrapper de la instancia
         * @param {string} instanceId - ID de la instancia
         * @param {string} symbolId - ID del símbolo
         */
        attachEventListeners: function(wrapper, instanceId, symbolId) {
            var self = this;

            // Double click para abrir inspector de instancia
            wrapper.addEventListener('dblclick', function(evento) {
                evento.stopPropagation();

                // Emitir evento para que el inspector lo capture
                document.dispatchEvent(new CustomEvent('vbp:instance:dblclick', {
                    detail: {
                        instanceId: instanceId,
                        symbolId: symbolId
                    }
                }));

                // También emitir evento de selección
                document.dispatchEvent(new CustomEvent('vbp:selection-changed', {
                    detail: {
                        elementIds: [instanceId]
                    }
                }));
            });

            // Click para seleccionar
            wrapper.addEventListener('click', function(evento) {
                // Solo si el click es directamente en el wrapper o badge
                if (evento.target === wrapper || evento.target.closest('.vbp-instance-badge')) {
                    evento.stopPropagation();

                    var store = window.Alpine && Alpine.store('vbp');
                    if (store) {
                        if (evento.shiftKey || evento.ctrlKey || evento.metaKey) {
                            store.toggleSelection(instanceId);
                        } else {
                            store.setSelection([instanceId]);
                        }
                    }

                    document.dispatchEvent(new CustomEvent('vbp:selection-changed', {
                        detail: {
                            elementIds: [instanceId]
                        }
                    }));
                }
            });

            // Hover para mostrar indicador
            wrapper.addEventListener('mouseenter', function() {
                wrapper.classList.add('vbp-instance-hover');
            });

            wrapper.addEventListener('mouseleave', function() {
                wrapper.classList.remove('vbp-instance-hover');
            });
        },

        /**
         * Refrescar una instancia específica
         * @param {string} instanceId - ID de la instancia
         */
        refreshInstance: function(instanceId) {
            var self = this;
            var cacheEntry = this.renderCache.get(instanceId);

            if (!cacheEntry || !cacheEntry.wrapper) {
                vbpLog.warn('InstanceRenderer: no hay cache para refrescar', instanceId);
                return;
            }

            var wrapper = cacheEntry.wrapper;
            var contenedor = wrapper.parentNode;

            if (!contenedor) {
                this.invalidateCache(instanceId);
                return;
            }

            // Obtener elemento actualizado del store
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var elemento = store.getElementDeep ? store.getElementDeep(instanceId) : store.getElement(instanceId);
            if (!elemento) {
                this.invalidateCache(instanceId);
                return;
            }

            // Re-renderizar
            var nuevoWrapper = this.renderInstance(elemento, null);

            // Reemplazar en el DOM
            contenedor.replaceChild(nuevoWrapper, wrapper);

            vbpLog.log('InstanceRenderer: instancia refrescada', instanceId);
        },

        /**
         * Refrescar todas las instancias de un símbolo
         * @param {string} symbolId - ID del símbolo
         */
        refreshInstancesOfSymbol: function(symbolId) {
            var self = this;
            var normalizedSymbolId = String(symbolId);

            this.renderCache.forEach(function(entry, instanceId) {
                if (String(entry.symbolId) === normalizedSymbolId) {
                    // Usar debounce para evitar múltiples re-renders
                    self.debouncedRefresh(instanceId);
                }
            });
        },

        /**
         * Refrescar con debounce
         * @param {string} instanceId - ID de la instancia
         */
        debouncedRefresh: function(instanceId) {
            var self = this;

            if (this.renderDebounceTimers[instanceId]) {
                clearTimeout(this.renderDebounceTimers[instanceId]);
            }

            this.renderDebounceTimers[instanceId] = setTimeout(function() {
                delete self.renderDebounceTimers[instanceId];
                self.refreshInstance(instanceId);
            }, 100);
        },

        /**
         * Actualizar indicador de overrides
         * @param {string} instanceId - ID de la instancia
         * @param {boolean} hasOverrides - Si tiene overrides
         */
        updateOverrideIndicator: function(instanceId, hasOverrides) {
            var cacheEntry = this.renderCache.get(instanceId);
            if (!cacheEntry || !cacheEntry.wrapper) return;

            var wrapper = cacheEntry.wrapper;
            var badge = wrapper.querySelector('.vbp-instance-badge');

            wrapper.dataset.hasOverrides = hasOverrides ? 'true' : 'false';

            if (badge) {
                if (hasOverrides) {
                    badge.classList.add('has-overrides');
                    badge.title = 'Instancia de Símbolo (modificada)';
                } else {
                    badge.classList.remove('has-overrides');
                    badge.title = 'Instancia de Símbolo';
                }
                badge.setAttribute('aria-label', badge.title);
            }
        },

        /**
         * Invalidar cache de una instancia
         * @param {string} instanceId - ID de la instancia
         */
        invalidateCache: function(instanceId) {
            this.renderCache.delete(instanceId);
        },

        /**
         * Limpiar todo el cache
         */
        clearCache: function() {
            this.renderCache.clear();
        },

        /**
         * Manejar evento de símbolo actualizado
         * @param {CustomEvent} evento
         */
        handleSymbolUpdated: function(evento) {
            var detail = evento.detail || {};
            var symbolId = detail.symbolId || (detail.symbol && detail.symbol.id);
            if (symbolId) {
                this.refreshInstancesOfSymbol(symbolId);
            }
        },

        /**
         * Manejar evento de instancia actualizada
         * @param {CustomEvent} evento
         */
        handleInstanceUpdated: function(evento) {
            var instanceId = evento.detail && evento.detail.instanceId;
            if (instanceId) {
                this.debouncedRefresh(instanceId);
            }
        },

        /**
         * Manejar evento de override cambiado
         * @param {CustomEvent} evento
         */
        handleOverrideChanged: function(evento) {
            var instanceId = evento.detail && evento.detail.instanceId;
            if (!instanceId) return;

            // Actualizar indicador visual
            var store = window.Alpine && Alpine.store('vbp');
            if (store) {
                var elemento = store.getElementDeep ? store.getElementDeep(instanceId) : store.getElement(instanceId);
                if (elemento) {
                    var overrides = elemento.overrides || (elemento.data && elemento.data.overrides) || {};
                    var hasOverrides = Object.keys(overrides).length > 0;
                    this.updateOverrideIndicator(instanceId, hasOverrides);
                }
            }

            // Refrescar contenido
            this.debouncedRefresh(instanceId);
        },

        /**
         * Inicializar observador de mutaciones
         */
        initMutationObserver: function() {
            var self = this;

            // Observar el canvas para detectar cuando se añaden/eliminan instancias
            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) {
                // Reintentar cuando el DOM esté listo
                setTimeout(function() {
                    self.initMutationObserver();
                }, 500);
                return;
            }

            this.mutationObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    // Detectar nodos añadidos
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && node.classList && node.classList.contains('vbp-symbol-instance')) {
                            var instanceId = node.dataset.elementId;
                            if (instanceId && !self.renderCache.has(instanceId)) {
                                // Nueva instancia añadida, guardar en cache
                                self.renderCache.set(instanceId, {
                                    wrapper: node,
                                    symbolId: node.dataset.symbolId,
                                    version: 1
                                });
                            }
                        }
                    });

                    // Detectar nodos eliminados
                    mutation.removedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && node.classList && node.classList.contains('vbp-symbol-instance')) {
                            var instanceId = node.dataset.elementId;
                            if (instanceId) {
                                self.invalidateCache(instanceId);
                            }
                        }
                    });
                });
            });

            this.mutationObserver.observe(canvas, {
                childList: true,
                subtree: true
            });

            vbpLog.log('InstanceRenderer: observador de mutaciones iniciado');
        },

        /**
         * Destruir el renderer
         */
        destroy: function() {
            // Limpiar timers de debounce
            for (var key in this.renderDebounceTimers) {
                if (this.renderDebounceTimers.hasOwnProperty(key)) {
                    clearTimeout(this.renderDebounceTimers[key]);
                }
            }
            this.renderDebounceTimers = {};

            // Limpiar event listeners
            if (this._eventHandlers.symbolUpdated) {
                document.removeEventListener('vbp:symbol:updated', this._eventHandlers.symbolUpdated);
            }
            if (this._eventHandlers.instanceUpdated) {
                document.removeEventListener('vbp:instance:updated', this._eventHandlers.instanceUpdated);
            }
            if (this._eventHandlers.overrideChanged) {
                document.removeEventListener('vbp:instance:override-changed', this._eventHandlers.overrideChanged);
            }
            if (this._eventHandlers.instanceDetached) {
                document.removeEventListener('vbp:instance:detached', this._eventHandlers.instanceDetached);
            }
            this._eventHandlers = {};

            // Desconectar observador
            if (this.mutationObserver) {
                this.mutationObserver.disconnect();
                this.mutationObserver = null;
            }

            // Limpiar cache
            this.clearCache();

            // Resetear flag de inicialización
            this._initialized = false;

            vbpLog.log('InstanceRenderer: destruido');
        }
    };

    // Inicializar cuando Alpine esté listo
    document.addEventListener('alpine:init', function() {
        window.VBPInstanceRenderer.init();
    });

    // Fallback si Alpine ya se inicializó
    if (window.Alpine && Alpine.store) {
        window.VBPInstanceRenderer.init();
    }
})();
