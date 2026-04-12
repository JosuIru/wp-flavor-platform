/**
 * Visual Builder Pro - Global Styles System
 *
 * Sistema de estilos globales reutilizables (.heading-1, .button-primary, etc.)
 * Permite definir clases CSS que se aplican a elementos y se actualizan globalmente.
 *
 * DIFERENCIA CON DESIGN TOKENS:
 * - Design Tokens: Variables CSS (colores, espaciados, tipografía)
 * - Global Styles: Clases CSS completas que combinan múltiples propiedades
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
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
     * Configuración inicial del módulo
     */
    var CONFIG = {
        REST_BASE: (window.VBP_Config && window.VBP_Config.restUrl) || '/wp-json/flavor-vbp/v1/',
        NONCE: (window.VBP_Config && window.VBP_Config.restNonce) || '',
        CSS_PREFIX: 'vbp-gs-',
        ENDPOINTS: (window.VBP_Config && window.VBP_Config.globalStyles && window.VBP_Config.globalStyles.endpoints) || {}
    };

    function getEndpoint(pathKey, fallbackPath) {
        if (CONFIG.ENDPOINTS && CONFIG.ENDPOINTS[pathKey]) {
            return CONFIG.ENDPOINTS[pathKey];
        }
        return CONFIG.REST_BASE + fallbackPath;
    }

    /**
     * Caché local de estilos globales
     */
    var stylesCache = null;
    var categoriesCache = null;

    /**
     * API de Global Styles
     */
    window.VBPGlobalStyles = {

        /**
         * Obtener todos los estilos globales
         * @param {boolean} forceRefresh - Forzar recarga desde servidor
         * @returns {Promise<Array>}
         */
        getAll: function(forceRefresh) {
            if (stylesCache && !forceRefresh) {
                return Promise.resolve(stylesCache);
            }

            return fetch(getEndpoint('list', 'global-styles'), {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': CONFIG.NONCE
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.styles) {
                    stylesCache = data.styles;
                    return data.styles;
                }
                throw new Error(data.message || 'Error al cargar estilos');
            });
        },

        /**
         * Obtener estilos agrupados por categoría
         * @returns {Promise<Object>}
         */
        getGrouped: function() {
            return fetch(getEndpoint('list', 'global-styles') + '?group=1', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': CONFIG.NONCE
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.categories) {
                    return data.categories;
                }
                throw new Error(data.message || 'Error al cargar estilos');
            });
        },

        /**
         * Obtener un estilo por ID
         * @param {string} styleId - ID del estilo
         * @returns {Promise<Object>}
         */
        get: function(styleId) {
            return fetch(getEndpoint('update', 'global-styles/{id}').replace('{id}', encodeURIComponent(styleId)), {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': CONFIG.NONCE
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.style) {
                    return data.style;
                }
                throw new Error(data.message || 'Estilo no encontrado');
            });
        },

        /**
         * Crear un nuevo estilo global
         * @param {Object} styleData - Datos del estilo
         * @returns {Promise<Object>}
         */
        create: function(styleData) {
            return fetch(getEndpoint('create', 'global-styles'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': CONFIG.NONCE
                },
                body: JSON.stringify(styleData)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.style) {
                    stylesCache = null; // Invalidar caché
                    VBPGlobalStyles.refreshCSS();
                    return data.style;
                }
                throw new Error(data.message || 'Error al crear estilo');
            });
        },

        /**
         * Actualizar un estilo existente
         * @param {string} styleId - ID del estilo
         * @param {Object} styleData - Datos a actualizar
         * @returns {Promise<Object>}
         */
        update: function(styleId, styleData) {
            return fetch(getEndpoint('update', 'global-styles/{id}').replace('{id}', encodeURIComponent(styleId)), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': CONFIG.NONCE
                },
                body: JSON.stringify(styleData)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.style) {
                    stylesCache = null; // Invalidar caché
                    VBPGlobalStyles.refreshCSS();
                    // Notificar a todos los elementos que usan este estilo
                    VBPGlobalStyles.notifyStyleChange(styleId, data.style);
                    return data.style;
                }
                throw new Error(data.message || 'Error al actualizar estilo');
            });
        },

        /**
         * Eliminar un estilo
         * @param {string} styleId - ID del estilo
         * @returns {Promise<boolean>}
         */
        delete: function(styleId) {
            return fetch(getEndpoint('delete', 'global-styles/{id}').replace('{id}', encodeURIComponent(styleId)), {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': CONFIG.NONCE
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    stylesCache = null; // Invalidar caché
                    VBPGlobalStyles.refreshCSS();
                    return true;
                }
                throw new Error(data.message || 'Error al eliminar estilo');
            });
        },

        /**
         * Obtener categorías disponibles
         * @returns {Promise<Array>}
         */
        getCategories: function() {
            if (categoriesCache) {
                return Promise.resolve(categoriesCache);
            }

            return fetch(getEndpoint('categories', 'global-styles/categories'), {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': CONFIG.NONCE
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.categories) {
                    categoriesCache = data.categories;
                    return data.categories;
                }
                throw new Error(data.message || 'Error al cargar categorías');
            });
        },

        /**
         * Aplicar un estilo global a un elemento
         * @param {string} elementId - ID del elemento
         * @param {string} styleId - ID del estilo global
         */
        applyToElement: function(elementId, styleId) {
            var store = Alpine.store('vbp');
            if (!store) {
                vbpLog.error('Global Styles: Store no disponible');
                return;
            }

            var element = store.getElementDeep(elementId);
            if (!element) {
                vbpLog.error('Global Styles: Elemento no encontrado', elementId);
                return;
            }

            // Guardar historial
            store.pushHistory('Aplicar estilo global');

            // Establecer referencia al estilo global
            element.globalStyleId = styleId;

            // Marcar como modificado
            store.isDirty = true;

            // Disparar evento
            document.dispatchEvent(new CustomEvent('vbp:global-style-applied', {
                detail: { elementId: elementId, styleId: styleId }
            }));

            vbpLog.log('Global Styles: Estilo aplicado', styleId, 'a elemento', elementId);
        },

        /**
         * Desenlazar un estilo global de un elemento
         * @param {string} elementId - ID del elemento
         * @param {boolean} keepStyles - Mantener los estilos actuales como locales
         */
        detachFromElement: function(elementId, keepStyles) {
            var store = Alpine.store('vbp');
            if (!store) {
                return;
            }

            var element = store.getElementDeep(elementId);
            if (!element || !element.globalStyleId) {
                return;
            }

            // Guardar historial
            store.pushHistory('Desenlazar estilo global');

            var previousStyleId = element.globalStyleId;

            if (keepStyles) {
                // Copiar estilos del global style al elemento
                VBPGlobalStyles.get(previousStyleId).then(function(globalStyle) {
                    if (globalStyle && globalStyle.styles) {
                        // Convertir estilos del formato global al formato de elemento
                        var elementStyles = VBPGlobalStyles.convertToElementStyles(globalStyle.styles);
                        element.styles = Object.assign({}, element.styles || {}, elementStyles);
                        element.localStyleOverrides = null;
                    }
                    element.globalStyleId = null;
                    store.isDirty = true;
                });
            } else {
                element.globalStyleId = null;
                element.localStyleOverrides = null;
                store.isDirty = true;
            }

            // Disparar evento
            document.dispatchEvent(new CustomEvent('vbp:global-style-detached', {
                detail: { elementId: elementId, previousStyleId: previousStyleId }
            }));
        },

        /**
         * Crear estilo global desde un elemento existente
         * @param {string} elementId - ID del elemento
         * @param {string} styleName - Nombre para el nuevo estilo
         * @param {string} category - Categoría del estilo
         * @returns {Promise<Object>}
         */
        createFromElement: function(elementId, styleName, category) {
            var store = Alpine.store('vbp');
            if (!store) {
                return Promise.reject(new Error('Store no disponible'));
            }

            var element = store.getElementDeep(elementId);
            if (!element) {
                return Promise.reject(new Error('Elemento no encontrado'));
            }

            // Extraer estilos relevantes del elemento
            var extractedStyles = VBPGlobalStyles.extractStylesFromElement(element);

            // Crear el estilo global
            return VBPGlobalStyles.create({
                name: styleName,
                category: category || VBPGlobalStyles.suggestCategory(element.type),
                description: 'Creado desde elemento ' + (element.name || element.type),
                styles: extractedStyles
            }).then(function(newStyle) {
                // Opcionalmente enlazar el elemento al nuevo estilo
                VBPGlobalStyles.applyToElement(elementId, newStyle.id);
                return newStyle;
            });
        },

        /**
         * Extraer estilos de un elemento para crear un global style
         * @param {Object} element - Elemento VBP
         * @returns {Object}
         */
        extractStylesFromElement: function(element) {
            var styles = element.styles || {};
            var extracted = {};

            // Mapeo de propiedades de elemento a propiedades de global style
            var mappings = {
                // Tipografía
                'typography.fontSize': 'fontSize',
                'typography.fontWeight': 'fontWeight',
                'typography.fontFamily': 'fontFamily',
                'typography.lineHeight': 'lineHeight',
                'typography.textAlign': 'textAlign',
                // Colores
                'colors.text': 'color',
                'colors.background': 'backgroundColor',
                // Espaciado
                'spacing.padding': 'padding',
                'spacing.margin': 'margin',
                // Bordes
                'borders.radius': 'borderRadius',
                'borders.width': 'borderWidth',
                'borders.color': 'borderColor',
                'borders.style': 'borderStyle',
                // Sombras
                'shadows.boxShadow': 'boxShadow',
                // Layout
                'layout.display': 'display',
                'layout.flexDirection': 'flexDirection',
                'layout.justifyContent': 'justifyContent',
                'layout.alignItems': 'alignItems',
                'layout.gap': 'gap'
            };

            Object.keys(mappings).forEach(function(path) {
                var value = VBPGlobalStyles.getNestedValue(styles, path);
                if (value && value !== '') {
                    // Manejar valores de spacing que son objetos
                    if (typeof value === 'object') {
                        // Convertir {top, right, bottom, left} a valor CSS
                        value = VBPGlobalStyles.spacingObjectToCSS(value);
                    }
                    if (value) {
                        extracted[mappings[path]] = value;
                    }
                }
            });

            return extracted;
        },

        /**
         * Convertir estilos de global style al formato de elemento VBP
         * @param {Object} globalStyles - Estilos del global style
         * @returns {Object}
         */
        convertToElementStyles: function(globalStyles) {
            var elementStyles = VBPStoreCatalog.getDefaultStyles();

            // Mapeo inverso
            var mappings = {
                'fontSize': 'typography.fontSize',
                'fontWeight': 'typography.fontWeight',
                'fontFamily': 'typography.fontFamily',
                'lineHeight': 'typography.lineHeight',
                'textAlign': 'typography.textAlign',
                'color': 'colors.text',
                'backgroundColor': 'colors.background',
                'borderRadius': 'borders.radius',
                'borderWidth': 'borders.width',
                'borderColor': 'borders.color',
                'borderStyle': 'borders.style',
                'boxShadow': 'shadows.boxShadow',
                'display': 'layout.display',
                'flexDirection': 'layout.flexDirection',
                'justifyContent': 'layout.justifyContent',
                'alignItems': 'layout.alignItems',
                'gap': 'layout.gap'
            };

            Object.keys(globalStyles).forEach(function(prop) {
                var path = mappings[prop];
                if (path) {
                    VBPGlobalStyles.setNestedValue(elementStyles, path, globalStyles[prop]);
                }
            });

            return elementStyles;
        },

        /**
         * Obtener valor anidado de un objeto
         * @param {Object} obj - Objeto
         * @param {string} path - Ruta (ej: 'typography.fontSize')
         * @returns {*}
         */
        getNestedValue: function(obj, path) {
            var parts = path.split('.');
            var current = obj;
            for (var i = 0; i < parts.length; i++) {
                if (current === null || current === undefined) {
                    return undefined;
                }
                current = current[parts[i]];
            }
            return current;
        },

        /**
         * Establecer valor anidado en un objeto
         * @param {Object} obj - Objeto
         * @param {string} path - Ruta
         * @param {*} value - Valor
         */
        setNestedValue: function(obj, path, value) {
            var parts = path.split('.');
            var current = obj;
            for (var i = 0; i < parts.length - 1; i++) {
                if (!current[parts[i]]) {
                    current[parts[i]] = {};
                }
                current = current[parts[i]];
            }
            current[parts[parts.length - 1]] = value;
        },

        /**
         * Convertir objeto de spacing a valor CSS
         * @param {Object} spacingObj - {top, right, bottom, left}
         * @returns {string}
         */
        spacingObjectToCSS: function(spacingObj) {
            if (!spacingObj || typeof spacingObj !== 'object') {
                return '';
            }

            var top = spacingObj.top || '0';
            var right = spacingObj.right || '0';
            var bottom = spacingObj.bottom || '0';
            var left = spacingObj.left || '0';

            // Si todos son iguales, devolver uno solo
            if (top === right && right === bottom && bottom === left) {
                return top === '0' ? '' : top;
            }

            // Si ninguno tiene valor
            if (top === '0' && right === '0' && bottom === '0' && left === '0') {
                return '';
            }

            return top + ' ' + right + ' ' + bottom + ' ' + left;
        },

        /**
         * Sugerir categoría basada en el tipo de elemento
         * @param {string} elementType - Tipo de elemento
         * @returns {string}
         */
        suggestCategory: function(elementType) {
            var typeMappings = {
                'heading': 'typography',
                'text': 'typography',
                'button': 'buttons',
                'container': 'containers',
                'columns': 'containers',
                'row': 'containers',
                'grid': 'containers',
                'section': 'containers',
                'card': 'containers'
            };

            return typeMappings[elementType] || 'custom';
        },

        /**
         * Obtener la clase CSS para un estilo global
         * @param {string} styleSlug - Slug del estilo
         * @returns {string}
         */
        getClassName: function(styleSlug) {
            return CONFIG.CSS_PREFIX + styleSlug;
        },

        /**
         * Refrescar CSS de estilos globales en el documento
         */
        refreshCSS: function() {
            fetch(getEndpoint('css', 'global-styles/css'), {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': CONFIG.NONCE
                }
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Error HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.css) {
                    // Actualizar o crear el elemento style
                    var styleElement = document.getElementById('vbp-global-styles-dynamic');
                    if (!styleElement) {
                        styleElement = document.createElement('style');
                        styleElement.id = 'vbp-global-styles-dynamic';
                        document.head.appendChild(styleElement);
                    }
                    styleElement.textContent = data.css;
                    vbpLog.log('Global Styles: CSS actualizado');
                }
            })
            .catch(function(error) {
                vbpLog.error('Global Styles: Error al refrescar CSS', error);
            });
        },

        /**
         * Notificar cambio de estilo global a elementos que lo usan
         * @param {string} styleId - ID del estilo modificado
         * @param {Object} updatedStyle - Estilo actualizado
         */
        notifyStyleChange: function(styleId, updatedStyle) {
            document.dispatchEvent(new CustomEvent('vbp:global-style-updated', {
                detail: { styleId: styleId, style: updatedStyle }
            }));

            // Forzar re-render de elementos afectados
            var store = Alpine.store('vbp');
            if (store && store.elements) {
                var affectedElements = VBPGlobalStyles.findElementsUsingStyle(store.elements, styleId);
                affectedElements.forEach(function(elementId) {
                    document.dispatchEvent(new CustomEvent('vbp:element-style-refresh', {
                        detail: { elementId: elementId }
                    }));
                });

                vbpLog.log('Global Styles: Notificados', affectedElements.length, 'elementos');
            }
        },

        /**
         * Encontrar todos los elementos que usan un estilo global
         * @param {Array} elements - Lista de elementos
         * @param {string} styleId - ID del estilo
         * @returns {Array<string>}
         */
        findElementsUsingStyle: function(elements, styleId) {
            var found = [];

            function searchInElements(elementsList) {
                elementsList.forEach(function(element) {
                    if (element.globalStyleId === styleId) {
                        found.push(element.id);
                    }
                    if (element.children && element.children.length > 0) {
                        searchInElements(element.children);
                    }
                });
            }

            searchInElements(elements);
            return found;
        },

        /**
         * Verificar si un elemento tiene overrides locales sobre el estilo global
         * @param {Object} element - Elemento VBP
         * @returns {boolean}
         */
        hasLocalOverrides: function(element) {
            return element && element.localStyleOverrides &&
                   Object.keys(element.localStyleOverrides).length > 0;
        },

        /**
         * Obtener los overrides locales de un elemento
         * @param {Object} element - Elemento VBP
         * @returns {Object}
         */
        getLocalOverrides: function(element) {
            return element && element.localStyleOverrides ? element.localStyleOverrides : {};
        },

        /**
         * Establecer override local sobre estilo global
         * @param {string} elementId - ID del elemento
         * @param {string} property - Propiedad CSS (camelCase)
         * @param {string} value - Valor
         */
        setLocalOverride: function(elementId, property, value) {
            var store = Alpine.store('vbp');
            if (!store) {
                return;
            }

            var element = store.getElementDeep(elementId);
            if (!element || !element.globalStyleId) {
                return;
            }

            if (!element.localStyleOverrides) {
                element.localStyleOverrides = {};
            }

            element.localStyleOverrides[property] = value;
            store.isDirty = true;

            document.dispatchEvent(new CustomEvent('vbp:local-override-set', {
                detail: { elementId: elementId, property: property, value: value }
            }));
        },

        /**
         * Eliminar override local
         * @param {string} elementId - ID del elemento
         * @param {string} property - Propiedad a eliminar
         */
        removeLocalOverride: function(elementId, property) {
            var store = Alpine.store('vbp');
            if (!store) {
                return;
            }

            var element = store.getElementDeep(elementId);
            if (!element || !element.localStyleOverrides) {
                return;
            }

            delete element.localStyleOverrides[property];

            // Si no quedan overrides, limpiar el objeto
            if (Object.keys(element.localStyleOverrides).length === 0) {
                element.localStyleOverrides = null;
            }

            store.isDirty = true;
        },

        /**
         * Restablecer todos los overrides locales
         * @param {string} elementId - ID del elemento
         */
        resetLocalOverrides: function(elementId) {
            var store = Alpine.store('vbp');
            if (!store) {
                return;
            }

            var element = store.getElementDeep(elementId);
            if (!element) {
                return;
            }

            store.pushHistory('Restablecer estilos locales');
            element.localStyleOverrides = null;
            store.isDirty = true;
        },

        /**
         * Obtener estilo computado de un elemento (global + overrides)
         * @param {Object} element - Elemento VBP
         * @returns {Promise<Object>}
         */
        getComputedStyle: function(element) {
            if (!element || !element.globalStyleId) {
                return Promise.resolve(element ? element.styles : {});
            }

            return VBPGlobalStyles.get(element.globalStyleId).then(function(globalStyle) {
                var baseStyles = globalStyle ? globalStyle.styles : {};
                var overrides = element.localStyleOverrides || {};

                // Combinar estilos: base + overrides
                return Object.assign({}, baseStyles, overrides);
            });
        },

        /**
         * Contar elementos que usan un estilo global
         * @param {string} styleId - ID del estilo
         * @returns {number}
         */
        countUsage: function(styleId) {
            var store = Alpine.store('vbp');
            if (!store || !store.elements) {
                return 0;
            }
            return VBPGlobalStyles.findElementsUsingStyle(store.elements, styleId).length;
        }
    };

    /**
     * Inicialización cuando Alpine está listo
     */
    document.addEventListener('alpine:init', function() {
        // Refrescar CSS al cargar
        setTimeout(function() {
            VBPGlobalStyles.refreshCSS();
        }, 100);

        vbpLog.log('Global Styles: Módulo inicializado');
    });

    /**
     * Escuchar cambios en el documento para actualizar elementos con global styles
     */
    document.addEventListener('vbp:document-loaded', function() {
        VBPGlobalStyles.refreshCSS();
    });

})();
