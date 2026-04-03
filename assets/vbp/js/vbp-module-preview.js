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

        /**
         * Inicializar sistema de preview
         */
        init: function() {
            var self = this;

            // Cargar cache desde localStorage si existe
            this.loadCacheFromStorage();

            // Escuchar cambios en elementos
            document.addEventListener('vbp:element:updated', function(e) {
                var elementId = e.detail && e.detail.id;
                if (elementId) {
                    self.invalidateCache(elementId);
                    self.debounceRefresh(elementId);
                }
            });

            // Escuchar cuando se añaden nuevos elementos
            document.addEventListener('vbp:element:added', function(e) {
                var element = e.detail && e.detail.element;
                if (element && self.isPreviewableElement(element)) {
                    self.loadPreview(element);
                }
            });

            // Observer para detectar elementos de módulo en el DOM
            this.initMutationObserver();

            // Limpiar cache periódicamente
            setInterval(function() {
                self.cleanExpiredCache();
            }, 60000);

            vbpLog.log('ModulePreview: Sistema de preview inicializado');
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
         * Obtener la clave de cache para un elemento
         */
        getCacheKey: function(element) {
            var shortcode = element.shortcode ||
                           (element.data && element.data.shortcode) ||
                           element.module || '';
            var attributes = element.data || {};
            return shortcode + ':' + JSON.stringify(attributes);
        },

        /**
         * Cargar preview de un elemento
         */
        loadPreview: function(element, forceRefresh) {
            var self = this;
            var elementId = element.id;

            // Verificar si ya está cargando
            if (this.loadingElements.has(elementId)) {
                return Promise.resolve(null);
            }

            // Verificar cache
            var cacheKey = this.getCacheKey(element);
            if (!forceRefresh && this.cache.has(cacheKey)) {
                var cached = this.cache.get(cacheKey);
                if (Date.now() - cached.timestamp < this.config.cacheTimeout) {
                    this.applyPreview(elementId, cached.html);
                    return Promise.resolve(cached.html);
                }
            }

            // Marcar como cargando
            this.loadingElements.add(elementId);
            this.showLoadingState(elementId);

            // Preparar datos para la petición
            var shortcode = element.shortcode ||
                           (element.data && element.data.shortcode) ||
                           element.module || '';
            var attributes = {};

            // Extraer atributos del elemento
            if (element.data) {
                Object.keys(element.data).forEach(function(key) {
                    if (key !== 'shortcode' && element.data[key] !== undefined) {
                        attributes[key] = element.data[key];
                    }
                });
            }

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
                var apiUrl = (window.vbpData && window.vbpData.restUrl) || '/wp-json/flavor-vbp/v1/';
                var nonce = (window.vbpData && window.vbpData.nonce) || '';

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
            var elementWrapper = document.querySelector('[data-element-id="' + elementId + '"]');
            if (!elementWrapper) return;

            var contentArea = elementWrapper.querySelector('.vbp-element-content');
            if (!contentArea) {
                contentArea = elementWrapper.querySelector('.vbp-module-content');
            }
            if (!contentArea) {
                contentArea = elementWrapper;
            }

            // Crear contenedor de preview si no existe
            var previewContainer = contentArea.querySelector('.vbp-module-preview');
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.className = 'vbp-module-preview';
                contentArea.innerHTML = '';
                contentArea.appendChild(previewContainer);
            }

            // Insertar HTML del preview
            previewContainer.innerHTML = html;
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
            var elementWrapper = document.querySelector('[data-element-id="' + elementId + '"]');
            if (!elementWrapper) return;

            var contentArea = elementWrapper.querySelector('.vbp-element-content') ||
                             elementWrapper.querySelector('.vbp-module-content') ||
                             elementWrapper;

            var previewContainer = contentArea.querySelector('.vbp-module-preview');
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.className = 'vbp-module-preview';
                contentArea.innerHTML = '';
                contentArea.appendChild(previewContainer);
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
            var elementWrapper = document.querySelector('[data-element-id="' + elementId + '"]');
            if (!elementWrapper) return;

            var contentArea = elementWrapper.querySelector('.vbp-element-content') ||
                             elementWrapper.querySelector('.vbp-module-content') ||
                             elementWrapper;

            var previewContainer = contentArea.querySelector('.vbp-module-preview');
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.className = 'vbp-module-preview';
                contentArea.innerHTML = '';
                contentArea.appendChild(previewContainer);
            }

            var elementData = this.getElementById(elementId);
            var shortcodeName = elementData ?
                (elementData.shortcode || elementData.module || 'módulo') : 'módulo';

            previewContainer.innerHTML = '<div class="vbp-module-preview__error">' +
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
                '</div>';
            previewContainer.classList.add('vbp-module-preview--error');
            previewContainer.classList.remove('vbp-module-preview--loaded', 'vbp-module-preview--loading');
        },

        /**
         * Reintentar cargar preview
         */
        retry: function(elementId) {
            var element = this.getElementById(elementId);
            if (element) {
                this.invalidateCache(elementId);
                this.loadPreview(element, true);
            }
        },

        /**
         * Obtener elemento por ID desde el store
         */
        getElementById: function(elementId) {
            if (typeof Alpine !== 'undefined' && Alpine.store) {
                var store = Alpine.store('vbp');
                if (store && typeof store.getElementById === 'function') {
                    return store.getElementById(elementId);
                }
            }
            return null;
        },

        /**
         * Invalidar cache para un elemento
         */
        invalidateCache: function(elementId) {
            var element = this.getElementById(elementId);
            if (element) {
                var cacheKey = this.getCacheKey(element);
                this.cache.delete(cacheKey);
            }
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
                var element = self.getElementById(elementId);
                if (element && self.isPreviewableElement(element)) {
                    self.loadPreview(element, true);
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
            var moduleElements = document.querySelectorAll('[data-element-type="shortcode"], [data-element-type="module-shortcode"], [data-element-type="widget"]');

            moduleElements.forEach(function(el) {
                var elementId = el.dataset.elementId;
                if (elementId) {
                    var element = self.getElementById(elementId);
                    if (element) {
                        self.loadPreview(element, true);
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
