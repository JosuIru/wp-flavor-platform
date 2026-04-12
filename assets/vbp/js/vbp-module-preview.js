/**
 * Visual Builder Pro - Module Preview System
 * Sistema de previsualización de módulos/widgets en el canvas
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

(function() {
    'use strict';

    // Fallback de vbpLog si no está definido
    if (!window.vbpLog) {
        window.vbpLog = {
            log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
            warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
            error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
        };
    }

    /**
     * Sistema de preview de módulos
     */
    window.vbpModulePreview = {
        __advanced: true,

        // Cache de previews renderizados
        cache: new Map(),

        // Elementos en proceso de carga
        loadingElements: new Set(),

        // Configuración
        config: {
            cacheTimeout: 5 * 60 * 1000, // 5 minutos
            retryAttempts: 3,
            retryDelay: 1000,
            debounceDelay: 300
        },

        // Timers para debounce
        debounceTimers: new Map(),

        // Referencias para cleanup (evitar memory leaks)
        _cleanupIntervalId: null,
        _eventHandlers: {
            elementUpdated: null,
            elementAdded: null
        },

        /**
         * Inicializar sistema de preview
         */
        init: function() {
            var self = this;

            // Cargar cache desde localStorage si existe
            this.loadCacheFromStorage();

            // Escuchar cambios en elementos (guardar referencia para cleanup)
            this._eventHandlers.elementUpdated = function(e) {
                var detail = e.detail || {};
                var elementId = detail.id;
                if (elementId && self.shouldRefreshFromUpdate(detail)) {
                    self.invalidateCache(elementId);
                    self.debounceRefresh(elementId);
                }
            };
            document.addEventListener('vbp:element:updated', this._eventHandlers.elementUpdated);

            // Escuchar cuando se añaden nuevos elementos (guardar referencia para cleanup)
            this._eventHandlers.elementAdded = function(e) {
                var element = e.detail && e.detail.element;
                if (element && self.isPreviewableElement(element)) {
                    self.loadPreview(element);
                }
            };
            document.addEventListener('vbp:element:added', this._eventHandlers.elementAdded);

            // Observer para detectar elementos de módulo en el DOM
            this.initMutationObserver();

            // Limpiar cache periódicamente (guardar ID para cleanup)
            this._cleanupIntervalId = setInterval(function() {
                self.cleanExpiredCache();
            }, 60000);

            vbpLog.log('ModulePreview: Sistema de preview inicializado');
        },

        /**
         * Destruir sistema y limpiar recursos
         */
        destroy: function() {
            // Limpiar interval de cleanup
            if (this._cleanupIntervalId) {
                clearInterval(this._cleanupIntervalId);
                this._cleanupIntervalId = null;
            }

            // Limpiar event listeners
            if (this._eventHandlers.elementUpdated) {
                document.removeEventListener('vbp:element:updated', this._eventHandlers.elementUpdated);
                this._eventHandlers.elementUpdated = null;
            }
            if (this._eventHandlers.elementAdded) {
                document.removeEventListener('vbp:element:added', this._eventHandlers.elementAdded);
                this._eventHandlers.elementAdded = null;
            }

            // Desconectar MutationObserver
            if (this._observer) {
                this._observer.disconnect();
                this._observer = null;
            }

            // Limpiar timers de debounce
            this.debounceTimers.forEach(function(timerId) {
                clearTimeout(timerId);
            });
            this.debounceTimers.clear();

            // Limpiar cache
            this.cache.clear();
            this.loadingElements.clear();

            vbpLog.log('ModulePreview: Sistema destruido y recursos liberados');
        },

        /**
         * Verificar si un elemento necesita preview
         */
        isPreviewableElement: function(element) {
            if (!element) return false;
            return element.shortcode ||
                   element.module ||
                   element.type === 'shortcode' ||
                   element.type === 'module-shortcode' ||
                   element.type === 'widget' ||
                   (element.data && element.data.shortcode);
        },

        /**
         * Localizar contenedor visible de preview para un módulo
         */
        getPreviewContainer: function(elementId) {
            var container = document.querySelector('.vbp-module-preview-container[data-element-id="' + elementId + '"]');
            if (container) {
                return container;
            }

            var iframe = document.querySelector('.vbp-canvas-iframe');
            if (iframe && iframe.contentDocument) {
                container = iframe.contentDocument.querySelector('.vbp-module-preview-container[data-element-id="' + elementId + '"]');
                if (container) {
                    return container;
                }
            }

            return document.querySelector('[data-element-id="' + elementId + '"]');
        },

        /**
         * Extraer atributos reales desde el elemento
         */
        getElementAttributes: function(element) {
            var attributes = {};

            if (!element || !element.data) {
                return attributes;
            }

            Object.keys(element.data).forEach(function(key) {
                if (key !== 'shortcode' && element.data[key] !== undefined) {
                    attributes[key] = element.data[key];
                }
            });

            return attributes;
        },

        shouldRefreshFromUpdate: function(detail) {
            if (!detail) {
                return false;
            }

            var element = detail.element || this.getElementById(detail.id);
            if (!this.isPreviewableElement(element)) {
                return false;
            }

            var changes = detail.changes || {};
            var relevantKeys = ['data', 'shortcode', 'module', 'type', 'preview_html', 'forcePreviewRefresh'];
            return relevantKeys.some(function(key) {
                return Object.prototype.hasOwnProperty.call(changes, key);
            });
        },

        /**
         * Leer atributos serializados en el DOM
         */
        getContainerAttributes: function(elementId) {
            var container = this.getPreviewContainer(elementId);
            if (!container || !container.dataset.attributes) {
                return {};
            }

            try {
                return JSON.parse(container.dataset.attributes);
            } catch (error) {
                vbpLog.warn('ModulePreview: no se pudieron parsear atributos del contenedor', error);
                return {};
            }
        },

        /**
         * Normalizar payload de preview para firma antigua y nueva
         */
        normalizePreviewRequest: function(elementOrId, shortcodeOrForceRefresh, attributesOrForceRefresh) {
            var isLegacyElement = elementOrId && typeof elementOrId === 'object';
            var element = isLegacyElement ? elementOrId : this.getElementById(elementOrId);
            var forceRefresh = false;
            var elementId;
            var shortcode;
            var attributes;

            if (isLegacyElement) {
                elementId = element.id;
                forceRefresh = shortcodeOrForceRefresh === true;
                shortcode = element.shortcode ||
                    (element.data && element.data.shortcode) ||
                    element.module ||
                    '';
                attributes = this.getElementAttributes(element);
            } else {
                elementId = elementOrId;
                forceRefresh = attributesOrForceRefresh === true;
                shortcode = shortcodeOrForceRefresh ||
                    (element && (element.shortcode || (element.data && element.data.shortcode) || element.module)) ||
                    '';
                attributes = attributesOrForceRefresh && typeof attributesOrForceRefresh === 'object'
                    ? attributesOrForceRefresh
                    : this.getContainerAttributes(elementId);

                if ((!attributes || !Object.keys(attributes).length) && element) {
                    attributes = this.getElementAttributes(element);
                }
            }

            return {
                element: element,
                elementId: elementId,
                shortcode: shortcode,
                attributes: attributes || {},
                forceRefresh: forceRefresh
            };
        },

        /**
         * Obtener la clave de cache para un elemento
         */
        getCacheKey: function(elementOrShortcode, explicitAttributes) {
            var shortcode = '';
            var attributes = explicitAttributes || {};

            if (typeof elementOrShortcode === 'string') {
                shortcode = elementOrShortcode;
            } else if (elementOrShortcode) {
                shortcode = elementOrShortcode.shortcode ||
                    (elementOrShortcode.data && elementOrShortcode.data.shortcode) ||
                    elementOrShortcode.module || '';
                attributes = this.getElementAttributes(elementOrShortcode);
            }

            return shortcode + ':' + JSON.stringify(attributes);
        },

        /**
         * Cargar preview de un elemento
         */
        loadPreview: function(elementOrId, shortcodeOrForceRefresh, attributesOrForceRefresh) {
            var self = this;
            var request = this.normalizePreviewRequest(elementOrId, shortcodeOrForceRefresh, attributesOrForceRefresh);
            var elementId = request.elementId;
            var shortcode = request.shortcode;
            var attributes = request.attributes;

            if (!elementId || !shortcode) {
                return Promise.resolve(null);
            }

            // Verificar si ya está cargando
            if (this.loadingElements.has(elementId)) {
                return Promise.resolve(null);
            }

            // Verificar cache
            var cacheKey = this.getCacheKey(shortcode, attributes);
            if (!request.forceRefresh && this.cache.has(cacheKey)) {
                var cached = this.cache.get(cacheKey);
                if (Date.now() - cached.timestamp < this.config.cacheTimeout) {
                    this.applyPreview(elementId, cached.html);
                    return Promise.resolve(cached.html);
                }
            }

            // Marcar como cargando
            this.loadingElements.add(elementId);
            this.showLoadingState(elementId);

            // Realizar petición AJAX
            return this.fetchPreview(shortcode, attributes)
                .then(function(html) {
                    // Guardar en cache
                    self.cache.set(cacheKey, {
                        html: html,
                        timestamp: Date.now()
                    });
                    self.saveCacheToStorage();

                    // Aplicar preview
                    self.applyPreview(elementId, html);
                    self.loadingElements.delete(elementId);

                    return html;
                })
                .catch(function(error) {
                    vbpLog.error('ModulePreview: Error cargando preview:', error);
                    self.showError(elementId, error.message || 'Error al cargar preview');
                    self.loadingElements.delete(elementId);
                    return null;
                });
        },

        /**
         * Hacer petición para obtener preview
         */
        fetchPreview: function(shortcode, attributes, attempt) {
            var self = this;
            attempt = attempt || 1;

            return new Promise(function(resolve, reject) {
                var apiUrl = (window.VBP_Config && window.VBP_Config.restUrl) ||
                    (window.vbpData && window.vbpData.restUrl) ||
                    '/wp-json/flavor-vbp/v1/';
                var nonce = (window.VBP_Config && window.VBP_Config.restNonce) ||
                    (window.vbpData && window.vbpData.nonce) ||
                    '';

                fetch(apiUrl + 'preview-shortcode', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': nonce
                    },
                    body: JSON.stringify({
                        shortcode: shortcode,
                        attributes: attributes
                    })
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP error ' + response.status);
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.success && data.html) {
                        resolve(data.html);
                    } else if (data.html) {
                        resolve(data.html);
                    } else {
                        reject(new Error(data.message || 'No se pudo obtener el preview'));
                    }
                })
                .catch(function(error) {
                    // Reintentar si no hemos excedido los intentos
                    if (attempt < self.config.retryAttempts) {
                        setTimeout(function() {
                            self.fetchPreview(shortcode, attributes, attempt + 1)
                                .then(resolve)
                                .catch(reject);
                        }, self.config.retryDelay * attempt);
                    } else {
                        reject(error);
                    }
                });
            });
        },

        /**
         * Aplicar preview HTML al elemento en el canvas
         */
        applyPreview: function(elementId, html) {
            var elementWrapper = this.getPreviewContainer(elementId);
            if (!elementWrapper) return;

            var previewContainer = elementWrapper.classList && elementWrapper.classList.contains('vbp-module-preview-container')
                ? elementWrapper.querySelector('.vbp-module-preview-content')
                : null;

            if (!previewContainer) {
                var contentArea = elementWrapper.querySelector('.vbp-element-content');
                if (!contentArea) {
                    contentArea = elementWrapper.querySelector('.vbp-module-content');
                }
                if (!contentArea) {
                    contentArea = elementWrapper;
                }

                previewContainer = contentArea.querySelector('.vbp-module-preview');
                if (!previewContainer) {
                    previewContainer = document.createElement('div');
                    previewContainer.className = 'vbp-module-preview';
                    contentArea.innerHTML = '';
                    contentArea.appendChild(previewContainer);
                }
            }

            // Insertar HTML del preview con controles homogéneos del editor
            previewContainer.innerHTML = this.buildPreviewFrame(elementId, html);
            previewContainer.classList.remove('vbp-module-preview--loading', 'vbp-module-preview--error');
            previewContainer.classList.add('vbp-module-preview--loaded');

            // Disparar evento
            elementWrapper.dispatchEvent(new CustomEvent('vbp:preview:loaded', {
                bubbles: true,
                detail: { elementId: elementId }
            }));
        },

        /**
         * Mostrar estado de carga
         */
        showLoadingState: function(elementId) {
            var elementWrapper = this.getPreviewContainer(elementId);
            if (!elementWrapper) return;

            var previewContainer = elementWrapper.classList && elementWrapper.classList.contains('vbp-module-preview-container')
                ? elementWrapper.querySelector('.vbp-module-preview-content')
                : null;

            if (!previewContainer) {
                var contentArea = elementWrapper.querySelector('.vbp-element-content') ||
                    elementWrapper.querySelector('.vbp-module-content') ||
                    elementWrapper;

                previewContainer = contentArea.querySelector('.vbp-module-preview');
                if (!previewContainer) {
                    previewContainer = document.createElement('div');
                    previewContainer.className = 'vbp-module-preview';
                    contentArea.innerHTML = '';
                    contentArea.appendChild(previewContainer);
                }
            }

            previewContainer.innerHTML = '<div class="vbp-module-preview__loader">' +
                '<div class="vbp-module-preview__spinner"></div>' +
                '<span class="vbp-module-preview__text">Cargando preview...</span>' +
                '</div>';
            previewContainer.classList.add('vbp-module-preview--loading');
            previewContainer.classList.remove('vbp-module-preview--loaded', 'vbp-module-preview--error');
        },

        /**
         * Mostrar error
         */
        showError: function(elementId, message) {
            var elementWrapper = this.getPreviewContainer(elementId);
            if (!elementWrapper) return;

            var previewContainer = elementWrapper.classList && elementWrapper.classList.contains('vbp-module-preview-container')
                ? elementWrapper.querySelector('.vbp-module-preview-content')
                : null;

            if (!previewContainer) {
                var contentArea = elementWrapper.querySelector('.vbp-element-content') ||
                    elementWrapper.querySelector('.vbp-module-content') ||
                    elementWrapper;

                previewContainer = contentArea.querySelector('.vbp-module-preview');
                if (!previewContainer) {
                    previewContainer = document.createElement('div');
                    previewContainer.className = 'vbp-module-preview';
                    contentArea.innerHTML = '';
                    contentArea.appendChild(previewContainer);
                }
            }

            var elementData = this.getElementById(elementId);
            var shortcodeName = elementData ?
                (elementData.shortcode || elementData.module || 'módulo') : 'módulo';

            previewContainer.innerHTML = this.buildPreviewFrame(elementId, '<div class="vbp-module-preview__error">' +
                '<svg class="vbp-module-preview__error-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<circle cx="12" cy="12" r="10"/>' +
                '<line x1="12" y1="8" x2="12" y2="12"/>' +
                '<line x1="12" y1="16" x2="12.01" y2="16"/>' +
                '</svg>' +
                '<span class="vbp-module-preview__error-text">' +
                'No se pudo cargar el preview de <strong>' + this.escapeHtml(shortcodeName) + '</strong>' +
                '</span>' +
                '<button type="button" class="vbp-module-preview__retry" onclick="window.vbpModulePreview.retry(\'' + elementId + '\')">' +
                'Reintentar' +
                '</button>' +
                '</div>');
            previewContainer.classList.add('vbp-module-preview--error');
            previewContainer.classList.remove('vbp-module-preview--loaded', 'vbp-module-preview--loading');
        },

        buildPreviewFrame: function(elementId, html) {
            var elementData = this.getElementById(elementId) || {};
            var moduleName = elementData.name ||
                elementData.module ||
                elementData.shortcode ||
                (elementData.data && elementData.data.shortcode) ||
                'Módulo';

            // Sanitizar HTML del servidor para prevenir scripts maliciosos
            var sanitizedHtml = this.sanitizeServerHtml(html);

            return '<div class="vbp-module-preview__frame">' +
                '<div class="vbp-module-badge">' +
                '<span class="vbp-module-badge__icon">⚡</span>' +
                '<span>' + this.escapeHtml(moduleName) + '</span>' +
                '</div>' +
                '<div class="vbp-module-preview__refresh-overlay">' +
                '<button type="button" class="vbp-module-preview__refresh-btn" onclick="window.vbpModulePreview.retry(\'' + elementId + '\')">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.13-3.36L23 10M1 14l5.36 4.36A9 9 0 0020.49 15"/></svg>' +
                '<span>Actualizar preview</span>' +
                '</button>' +
                '</div>' +
                sanitizedHtml +
                '</div>';
        },

        /**
         * Sanitizar HTML del servidor para prevenir XSS
         * Permite HTML estructural pero elimina scripts y handlers peligrosos
         */
        sanitizeServerHtml: function(html) {
            if (!html || typeof html !== 'string') {
                return '';
            }

            // Crear documento temporal para parsear
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');

            // Eliminar elementos peligrosos
            var dangerousTags = ['script', 'iframe', 'object', 'embed', 'form'];
            dangerousTags.forEach(function(tag) {
                var elements = doc.querySelectorAll(tag);
                elements.forEach(function(el) {
                    el.remove();
                });
            });

            // Eliminar atributos de eventos (onclick, onerror, etc.)
            var allElements = doc.body.querySelectorAll('*');
            allElements.forEach(function(el) {
                var attrs = Array.from(el.attributes);
                attrs.forEach(function(attr) {
                    var name = attr.name.toLowerCase();
                    // Eliminar handlers de eventos
                    if (name.startsWith('on')) {
                        el.removeAttribute(attr.name);
                    }
                    // Eliminar javascript: en href/src
                    if ((name === 'href' || name === 'src') &&
                        attr.value.toLowerCase().trim().startsWith('javascript:')) {
                        el.removeAttribute(attr.name);
                    }
                });
            });

            return doc.body.innerHTML;
        },

        /**
         * Reintentar cargar preview
         */
        retry: function(elementId) {
            var request = this.normalizePreviewRequest(elementId);
            if (!request.elementId || !request.shortcode) {
                return;
            }

            this.invalidateCache(elementId);
            this.loadPreview(request.elementId, request.shortcode, request.attributes);
        },

        /**
         * Obtener elemento por ID desde el store
         */
        getElementById: function(elementId) {
            if (typeof Alpine !== 'undefined' && Alpine.store) {
                var store = Alpine.store('vbp');
                if (store) {
                    if (typeof store.getElementById === 'function') {
                        return store.getElementById(elementId);
                    }
                    if (typeof store.getElementDeep === 'function') {
                        return store.getElementDeep(elementId);
                    }
                    if (typeof store.getElement === 'function') {
                        return store.getElement(elementId);
                    }
                }
            }
            return null;
        },

        /**
         * Invalidar cache para un elemento
         */
        invalidateCache: function(elementId) {
            var request = this.normalizePreviewRequest(elementId);
            if (!request.shortcode) {
                return;
            }

            var cacheKey = this.getCacheKey(request.shortcode, request.attributes);
            this.cache.delete(cacheKey);
        },

        /**
         * Debounce para refrescar preview
         */
        debounceRefresh: function(elementId) {
            var self = this;

            // Cancelar timer existente
            if (this.debounceTimers.has(elementId)) {
                clearTimeout(this.debounceTimers.get(elementId));
            }

            // Crear nuevo timer
            var timer = setTimeout(function() {
                self.debounceTimers.delete(elementId);
                var request = self.normalizePreviewRequest(elementId);
                if (request.shortcode) {
                    self.loadPreview(elementId, request.shortcode, request.attributes);
                }
            }, this.config.debounceDelay);

            this.debounceTimers.set(elementId, timer);
        },

        /**
         * Limpiar cache expirado
         */
        cleanExpiredCache: function() {
            var now = Date.now();
            var timeout = this.config.cacheTimeout;
            var self = this;

            this.cache.forEach(function(value, key) {
                if (now - value.timestamp > timeout) {
                    self.cache.delete(key);
                }
            });
        },

        /**
         * Limpiar todo el cache
         */
        clearCache: function() {
            this.cache.clear();
            localStorage.removeItem('vbp_module_preview_cache');
        },

        /**
         * Recargar todos los previews visibles
         */
        reloadAll: function() {
            var self = this;
            var moduleElements = document.querySelectorAll('.vbp-module-preview-container[data-element-id], [data-element-type="shortcode"], [data-element-type="module-shortcode"], [data-element-type="widget"]');

            moduleElements.forEach(function(el) {
                var elementId = el.dataset.elementId;
                if (elementId) {
                    var request = self.normalizePreviewRequest(elementId);
                    if (request.shortcode) {
                        self.loadPreview(elementId, request.shortcode, request.attributes);
                    }
                }
            });
        },

        /**
         * Guardar cache en localStorage
         */
        saveCacheToStorage: function() {
            try {
                var cacheObj = {};
                this.cache.forEach(function(value, key) {
                    cacheObj[key] = value;
                });
                localStorage.setItem('vbp_module_preview_cache', JSON.stringify(cacheObj));
            } catch (e) {
                // localStorage lleno o no disponible
            }
        },

        /**
         * Cargar cache desde localStorage
         */
        loadCacheFromStorage: function() {
            try {
                var stored = localStorage.getItem('vbp_module_preview_cache');
                if (stored) {
                    var cacheObj = JSON.parse(stored);
                    var self = this;
                    Object.keys(cacheObj).forEach(function(key) {
                        self.cache.set(key, cacheObj[key]);
                    });
                }
            } catch (e) {
                // Error al leer cache
            }
        },

        /**
         * Inicializar MutationObserver para detectar nuevos elementos
         */
        initMutationObserver: function() {
            var self = this;
            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) return;

            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            // Verificar si el nodo o sus hijos son módulos
                            var moduleElements = node.matches && node.matches('[data-element-type="shortcode"], [data-element-type="module-shortcode"], [data-element-type="widget"]') ?
                                [node] :
                                (node.querySelectorAll ? Array.from(node.querySelectorAll('[data-element-type="shortcode"], [data-element-type="module-shortcode"], [data-element-type="widget"]')) : []);

                            moduleElements.forEach(function(el) {
                                var elementId = el.dataset.elementId;
                                if (elementId) {
                                    var element = self.getElementById(elementId);
                                    if (element) {
                                        self.loadPreview(element);
                                    }
                                }
                            });
                        }
                    });
                });
            });

            observer.observe(canvas, {
                childList: true,
                subtree: true
            });
        },

        /**
         * Escapar HTML para evitar XSS
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.vbpModulePreview.init();
        });
    } else {
        window.vbpModulePreview.init();
    }

})();
