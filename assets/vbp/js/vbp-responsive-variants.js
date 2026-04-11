/**
 * VBP Responsive Variants - Sistema de variantes de diseño por breakpoint
 *
 * Permite definir overrides de propiedades para diferentes dispositivos,
 * similar al sistema de Figma para diseño responsive.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

(function() {
    'use strict';

    /**
     * Breakpoints predefinidos con configuracion
     */
    var RESPONSIVE_BREAKPOINTS = {
        desktop: {
            min: 1200,
            max: null,
            icon: 'monitor',
            label: 'Desktop',
            shortLabel: 'D',
            cssMediaQuery: '@media (min-width: 1200px)',
            canvasWidth: 1200
        },
        laptop: {
            min: 992,
            max: 1199,
            icon: 'laptop',
            label: 'Laptop',
            shortLabel: 'L',
            cssMediaQuery: '@media (min-width: 992px) and (max-width: 1199px)',
            canvasWidth: 1024
        },
        tablet: {
            min: 768,
            max: 991,
            icon: 'tablet',
            label: 'Tablet',
            shortLabel: 'T',
            cssMediaQuery: '@media (min-width: 768px) and (max-width: 991px)',
            canvasWidth: 768
        },
        mobile: {
            min: 0,
            max: 767,
            icon: 'smartphone',
            label: 'Mobile',
            shortLabel: 'M',
            cssMediaQuery: '@media (max-width: 767px)',
            canvasWidth: 375
        }
    };

    /**
     * Orden de breakpoints para cascada CSS (mobile-first o desktop-first)
     */
    var BREAKPOINT_ORDER_DESKTOP_FIRST = ['desktop', 'laptop', 'tablet', 'mobile'];
    var BREAKPOINT_ORDER_MOBILE_FIRST = ['mobile', 'tablet', 'laptop', 'desktop'];

    /**
     * Propiedades que pueden tener overrides responsive
     */
    var RESPONSIVE_PROPERTIES = {
        layout: ['display', 'flexDirection', 'justifyContent', 'alignItems', 'gap', 'gridTemplateColumns'],
        sizing: ['width', 'height', 'minWidth', 'maxWidth', 'minHeight', 'maxHeight'],
        spacing: ['margin', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft',
                  'padding', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft'],
        typography: ['fontSize', 'lineHeight', 'letterSpacing', 'textAlign'],
        positioning: ['position', 'top', 'right', 'bottom', 'left', 'zIndex'],
        visibility: ['hidden', 'opacity', 'visibility'],
        order: ['order', 'flexGrow', 'flexShrink']
    };

    /**
     * Sistema principal de Responsive Variants
     */
    window.VBPResponsiveVariants = {
        /**
         * Breakpoint activo actualmente
         */
        currentBreakpoint: 'desktop',

        /**
         * Ancho del canvas actual
         */
        canvasWidth: 1200,

        /**
         * Configuracion de breakpoints
         */
        breakpoints: RESPONSIVE_BREAKPOINTS,

        /**
         * Modo de orden CSS (desktop-first por defecto)
         */
        cssOrderMode: 'desktop-first',

        /**
         * Cache de estilos computados por breakpoint
         */
        styleCache: new Map(),

        /**
         * Elementos con overrides visualizados
         */
        elementsWithOverrides: new Set(),

        /**
         * Contenedor para indicadores visuales
         */
        indicatorsContainer: null,

        /**
         * ResizeObserver para el canvas
         */
        resizeObserver: null,

        /**
         * Inicializa el sistema de responsive variants
         */
        init: function() {
            this.createIndicatorsContainer();
            this.initCanvasResizeHandle();
            this.bindEvents();
            this.initBreakpointRuler();
            this.syncWithStore();
        },

        /**
         * Crea el contenedor para indicadores visuales
         */
        createIndicatorsContainer: function() {
            var existingContainer = document.getElementById('vbp-responsive-indicators');
            if (existingContainer) {
                this.indicatorsContainer = existingContainer;
                return;
            }

            var containerElement = document.createElement('div');
            containerElement.className = 'vbp-responsive-indicators';
            containerElement.id = 'vbp-responsive-indicators';

            var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
            if (canvasWrapper) {
                canvasWrapper.appendChild(containerElement);
                this.indicatorsContainer = containerElement;
            }
        },

        /**
         * Vincula eventos del sistema
         */
        bindEvents: function() {
            var self = this;

            // Escuchar cambio de breakpoint
            document.addEventListener('vbp:breakpoint:changed', function(event) {
                if (event.detail && event.detail.breakpoint) {
                    self.setBreakpoint(event.detail.breakpoint);
                }
            });

            // Escuchar seleccion de elementos
            document.addEventListener('vbp:selection:changed', function() {
                self.updateOverrideIndicators();
            });

            // Escuchar actualizacion de elementos
            document.addEventListener('vbp:element:updated', function(event) {
                if (event.detail && event.detail.id) {
                    self.invalidateCache(event.detail.id);
                    self.updateOverrideIndicators();
                }
            });

            // Escuchar resize del canvas
            document.addEventListener('vbp:canvas:resized', function(event) {
                if (event.detail && event.detail.width) {
                    self.handleCanvasResize(event.detail.width);
                }
            });

            // Escuchar teclas de atajo para cambio de breakpoint
            document.addEventListener('keydown', function(event) {
                self.handleKeyboardShortcuts(event);
            });
        },

        /**
         * Maneja atajos de teclado para breakpoints
         *
         * @param {KeyboardEvent} event Evento de teclado
         */
        handleKeyboardShortcuts: function(event) {
            // Alt + 1/2/3/4 para cambiar breakpoints
            if (event.altKey && !event.ctrlKey && !event.metaKey) {
                var breakpointIndex = parseInt(event.key, 10);
                if (breakpointIndex >= 1 && breakpointIndex <= 4) {
                    var breakpointKeys = BREAKPOINT_ORDER_DESKTOP_FIRST;
                    var targetBreakpoint = breakpointKeys[breakpointIndex - 1];
                    if (targetBreakpoint) {
                        event.preventDefault();
                        this.setBreakpoint(targetBreakpoint);
                    }
                }
            }
        },

        /**
         * Sincroniza con el store de Alpine
         */
        syncWithStore: function() {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            // Extender el store con funcionalidad responsive
            if (!store.responsive) {
                store.responsive = {
                    currentBreakpoint: 'desktop',
                    breakpoints: RESPONSIVE_BREAKPOINTS,
                    canvasWidth: 1200,
                    showBreakpointRuler: true,
                    highlightOverrides: true
                };
            }

            // Sincronizar breakpoint inicial
            this.currentBreakpoint = store.responsive.currentBreakpoint || 'desktop';
            this.canvasWidth = store.responsive.canvasWidth || 1200;
        },

        /**
         * Establece el breakpoint activo
         *
         * @param {string} breakpoint ID del breakpoint
         */
        setBreakpoint: function(breakpoint) {
            if (!this.breakpoints[breakpoint]) {
                console.warn('[VBP Responsive] Breakpoint no valido:', breakpoint);
                return;
            }

            var previousBreakpoint = this.currentBreakpoint;
            this.currentBreakpoint = breakpoint;

            var breakpointConfig = this.breakpoints[breakpoint];
            this.canvasWidth = breakpointConfig.canvasWidth;

            // Actualizar store
            var store = window.Alpine && Alpine.store('vbp');
            if (store) {
                store.activeBreakpoint = breakpoint;
                store.devicePreview = breakpoint;
                if (store.responsive) {
                    store.responsive.currentBreakpoint = breakpoint;
                    store.responsive.canvasWidth = this.canvasWidth;
                }
            }

            // Actualizar canvas
            this.resizeCanvas(this.canvasWidth);

            // Actualizar UI
            this.updateBreakpointSelector();
            this.updateOverrideIndicators();
            this.updateBreakpointRuler();

            // Disparar evento
            document.dispatchEvent(new CustomEvent('vbp:responsive:breakpointChanged', {
                detail: {
                    breakpoint: breakpoint,
                    previousBreakpoint: previousBreakpoint,
                    canvasWidth: this.canvasWidth,
                    config: breakpointConfig
                }
            }));

            // Mostrar indicador visual
            this.showBreakpointIndicator(breakpoint);
        },

        /**
         * Muestra indicador visual de cambio de breakpoint
         *
         * @param {string} breakpoint ID del breakpoint
         */
        showBreakpointIndicator: function(breakpoint) {
            var existingIndicator = document.getElementById('vbp-breakpoint-indicator');
            if (existingIndicator) {
                existingIndicator.remove();
            }

            var breakpointConfig = this.breakpoints[breakpoint];
            var indicatorElement = document.createElement('div');
            indicatorElement.id = 'vbp-breakpoint-indicator';
            indicatorElement.className = 'vbp-breakpoint-indicator';
            indicatorElement.innerHTML = '<span class="vbp-breakpoint-indicator__icon">' +
                this.getBreakpointIcon(breakpoint) +
                '</span><span class="vbp-breakpoint-indicator__label">' +
                breakpointConfig.label + ' (' + this.canvasWidth + 'px)</span>';

            document.body.appendChild(indicatorElement);

            // Animacion de salida
            setTimeout(function() {
                indicatorElement.classList.add('is-fading');
                setTimeout(function() {
                    indicatorElement.remove();
                }, 300);
            }, 1500);
        },

        /**
         * Obtiene el icono SVG para un breakpoint
         *
         * @param {string} breakpoint ID del breakpoint
         * @returns {string} HTML del icono SVG
         */
        getBreakpointIcon: function(breakpoint) {
            var icons = {
                desktop: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
                laptop: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="12" rx="2"/><path d="M1 18h22"/></svg>',
                tablet: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="12" cy="18" r="1"/></svg>',
                mobile: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="20" rx="2"/><circle cx="12" cy="18" r="1"/></svg>'
            };
            return icons[breakpoint] || icons.desktop;
        },

        /**
         * Redimensiona el canvas al ancho especificado
         *
         * @param {number} width Ancho en pixels
         */
        resizeCanvas: function(width) {
            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) return;

            canvas.style.width = width + 'px';
            canvas.style.maxWidth = width + 'px';

            // Disparar evento para otros sistemas
            document.dispatchEvent(new CustomEvent('vbp:canvas:resized', {
                detail: { width: width, breakpoint: this.currentBreakpoint }
            }));
        },

        /**
         * Maneja el resize del canvas para detectar breakpoint
         *
         * @param {number} width Ancho actual del canvas
         */
        handleCanvasResize: function(width) {
            var detectedBreakpoint = this.detectBreakpoint(width);
            if (detectedBreakpoint !== this.currentBreakpoint) {
                // No cambiar automaticamente, solo mostrar sugerencia
                this.showBreakpointSuggestion(detectedBreakpoint, width);
            }
        },

        /**
         * Detecta el breakpoint correspondiente a un ancho
         *
         * @param {number} width Ancho en pixels
         * @returns {string} ID del breakpoint
         */
        detectBreakpoint: function(width) {
            for (var breakpointId in this.breakpoints) {
                var breakpointConfig = this.breakpoints[breakpointId];
                var matchesMin = width >= breakpointConfig.min;
                var matchesMax = breakpointConfig.max === null || width <= breakpointConfig.max;

                if (matchesMin && matchesMax) {
                    return breakpointId;
                }
            }
            return 'desktop';
        },

        /**
         * Muestra sugerencia de cambio de breakpoint
         *
         * @param {string} suggestedBreakpoint Breakpoint sugerido
         * @param {number} currentWidth Ancho actual
         */
        showBreakpointSuggestion: function(suggestedBreakpoint, currentWidth) {
            var suggestionElement = document.getElementById('vbp-breakpoint-suggestion');
            if (suggestionElement) {
                suggestionElement.remove();
            }

            var breakpointConfig = this.breakpoints[suggestedBreakpoint];
            suggestionElement = document.createElement('div');
            suggestionElement.id = 'vbp-breakpoint-suggestion';
            suggestionElement.className = 'vbp-breakpoint-suggestion';
            suggestionElement.innerHTML = '<span>El canvas coincide con <strong>' +
                breakpointConfig.label + '</strong></span>' +
                '<button class="vbp-breakpoint-suggestion__btn" data-breakpoint="' +
                suggestedBreakpoint + '">Cambiar</button>' +
                '<button class="vbp-breakpoint-suggestion__close">&times;</button>';

            document.body.appendChild(suggestionElement);

            var self = this;
            suggestionElement.querySelector('.vbp-breakpoint-suggestion__btn').addEventListener('click', function() {
                self.setBreakpoint(suggestedBreakpoint);
                suggestionElement.remove();
            });

            suggestionElement.querySelector('.vbp-breakpoint-suggestion__close').addEventListener('click', function() {
                suggestionElement.remove();
            });

            // Auto-cerrar despues de 5 segundos
            setTimeout(function() {
                if (suggestionElement.parentNode) {
                    suggestionElement.remove();
                }
            }, 5000);
        },

        /**
         * Obtiene las props efectivas para un elemento en el breakpoint actual
         *
         * @param {string} elementId ID del elemento
         * @returns {Object} Props combinadas (base + overrides)
         */
        getEffectiveProps: function(elementId) {
            var cacheKey = elementId + '_' + this.currentBreakpoint;

            // Verificar cache
            if (this.styleCache.has(cacheKey)) {
                return this.styleCache.get(cacheKey);
            }

            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return {};

            var element = store.getElementDeep(elementId);
            if (!element) return {};

            // Obtener estilos base (desktop)
            var baseStyles = element.styles || {};

            // Si estamos en desktop, devolver base
            if (this.currentBreakpoint === 'desktop') {
                this.styleCache.set(cacheKey, baseStyles);
                return baseStyles;
            }

            // Obtener overrides para el breakpoint actual
            var responsive = element.responsive || {};
            var overrides = responsive[this.currentBreakpoint] || {};

            // Combinar estilos con cascada
            var effectiveStyles = this.mergeStylesWithCascade(baseStyles, element.responsive || {});

            this.styleCache.set(cacheKey, effectiveStyles);
            return effectiveStyles;
        },

        /**
         * Combina estilos base con overrides respetando la cascada
         *
         * @param {Object} baseStyles Estilos base (desktop)
         * @param {Object} responsiveOverrides Objeto con overrides por breakpoint
         * @returns {Object} Estilos combinados
         */
        mergeStylesWithCascade: function(baseStyles, responsiveOverrides) {
            var effectiveStyles = JSON.parse(JSON.stringify(baseStyles));
            var breakpointOrder = this.cssOrderMode === 'desktop-first'
                ? BREAKPOINT_ORDER_DESKTOP_FIRST
                : BREAKPOINT_ORDER_MOBILE_FIRST;

            // Encontrar indice del breakpoint actual
            var currentIndex = breakpointOrder.indexOf(this.currentBreakpoint);
            if (currentIndex === -1) return effectiveStyles;

            // Aplicar overrides en orden de cascada
            for (var i = 0; i <= currentIndex; i++) {
                var breakpointId = breakpointOrder[i];
                if (breakpointId === 'desktop') continue; // Desktop es la base

                var breakpointOverrides = responsiveOverrides[breakpointId];
                if (breakpointOverrides) {
                    this.deepMerge(effectiveStyles, breakpointOverrides);
                }
            }

            return effectiveStyles;
        },

        /**
         * Mezcla profunda de objetos
         *
         * @param {Object} targetObject Objeto destino
         * @param {Object} sourceObject Objeto fuente
         */
        deepMerge: function(targetObject, sourceObject) {
            for (var key in sourceObject) {
                if (sourceObject.hasOwnProperty(key)) {
                    if (sourceObject[key] !== null &&
                        typeof sourceObject[key] === 'object' &&
                        !Array.isArray(sourceObject[key])) {
                        if (!targetObject[key]) {
                            targetObject[key] = {};
                        }
                        this.deepMerge(targetObject[key], sourceObject[key]);
                    } else {
                        targetObject[key] = sourceObject[key];
                    }
                }
            }
        },

        /**
         * Establece un override para un elemento en un breakpoint
         *
         * @param {string} elementId ID del elemento
         * @param {string} breakpoint ID del breakpoint
         * @param {Object} overrideProps Propiedades a sobrescribir
         */
        setOverride: function(elementId, breakpoint, overrideProps) {
            if (breakpoint === 'desktop') {
                console.warn('[VBP Responsive] No se pueden crear overrides para desktop (es la base)');
                return;
            }

            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep(elementId);
            if (!element) return;

            // Crear estructura responsive si no existe
            var responsive = JSON.parse(JSON.stringify(element.responsive || {}));
            if (!responsive[breakpoint]) {
                responsive[breakpoint] = {};
            }

            // Aplicar overrides
            this.deepMerge(responsive[breakpoint], overrideProps);

            // Actualizar elemento
            store.updateElement(elementId, { responsive: responsive });

            // Invalidar cache
            this.invalidateCache(elementId);

            // Disparar evento
            document.dispatchEvent(new CustomEvent('vbp:responsive:overrideSet', {
                detail: {
                    elementId: elementId,
                    breakpoint: breakpoint,
                    overrides: overrideProps
                }
            }));
        },

        /**
         * Elimina un override especifico de una propiedad
         *
         * @param {string} elementId ID del elemento
         * @param {string} breakpoint ID del breakpoint
         * @param {string} propertyName Nombre de la propiedad
         */
        clearOverride: function(elementId, breakpoint, propertyName) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep(elementId);
            if (!element || !element.responsive || !element.responsive[breakpoint]) return;

            var responsive = JSON.parse(JSON.stringify(element.responsive));

            // Eliminar propiedad usando path
            this.deleteNestedProperty(responsive[breakpoint], propertyName);

            // Si el breakpoint queda vacio, eliminarlo
            if (Object.keys(responsive[breakpoint]).length === 0) {
                delete responsive[breakpoint];
            }

            store.updateElement(elementId, { responsive: responsive });
            this.invalidateCache(elementId);
        },

        /**
         * Elimina todos los overrides de un breakpoint para un elemento
         *
         * @param {string} elementId ID del elemento
         * @param {string} breakpoint ID del breakpoint
         */
        clearAllOverrides: function(elementId, breakpoint) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep(elementId);
            if (!element || !element.responsive) return;

            var responsive = JSON.parse(JSON.stringify(element.responsive));
            delete responsive[breakpoint];

            store.updateElement(elementId, { responsive: responsive });
            this.invalidateCache(elementId);

            document.dispatchEvent(new CustomEvent('vbp:responsive:overridesCleared', {
                detail: { elementId: elementId, breakpoint: breakpoint }
            }));
        },

        /**
         * Elimina una propiedad anidada usando path con puntos
         *
         * @param {Object} targetObject Objeto destino
         * @param {string} propertyPath Path de la propiedad (ej: 'spacing.padding')
         */
        deleteNestedProperty: function(targetObject, propertyPath) {
            var pathParts = propertyPath.split('.');
            var currentObject = targetObject;

            for (var i = 0; i < pathParts.length - 1; i++) {
                if (!currentObject[pathParts[i]]) return;
                currentObject = currentObject[pathParts[i]];
            }

            delete currentObject[pathParts[pathParts.length - 1]];
        },

        /**
         * Copia el layout de un breakpoint a otro
         *
         * @param {string} elementId ID del elemento
         * @param {string} fromBreakpoint Breakpoint origen
         * @param {string} toBreakpoint Breakpoint destino
         * @param {Array} properties Propiedades especificas a copiar (opcional)
         */
        copyLayout: function(elementId, fromBreakpoint, toBreakpoint, properties) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep(elementId);
            if (!element) return;

            var sourceStyles;
            if (fromBreakpoint === 'desktop') {
                sourceStyles = element.styles || {};
            } else if (element.responsive && element.responsive[fromBreakpoint]) {
                sourceStyles = element.responsive[fromBreakpoint];
            } else {
                return;
            }

            // Si se especifican propiedades, filtrar
            var stylesToCopy;
            if (properties && properties.length > 0) {
                stylesToCopy = {};
                for (var i = 0; i < properties.length; i++) {
                    var propertyPath = properties[i];
                    var value = this.getNestedValue(sourceStyles, propertyPath);
                    if (value !== undefined) {
                        this.setNestedValue(stylesToCopy, propertyPath, value);
                    }
                }
            } else {
                stylesToCopy = JSON.parse(JSON.stringify(sourceStyles));
            }

            // Aplicar al destino
            if (toBreakpoint === 'desktop') {
                var styles = JSON.parse(JSON.stringify(element.styles || {}));
                this.deepMerge(styles, stylesToCopy);
                store.updateElement(elementId, { styles: styles });
            } else {
                this.setOverride(elementId, toBreakpoint, stylesToCopy);
            }

            this.invalidateCache(elementId);
        },

        /**
         * Obtiene valor anidado usando path con puntos
         *
         * @param {Object} sourceObject Objeto fuente
         * @param {string} propertyPath Path de la propiedad
         * @returns {*} Valor encontrado o undefined
         */
        getNestedValue: function(sourceObject, propertyPath) {
            var pathParts = propertyPath.split('.');
            var currentValue = sourceObject;

            for (var i = 0; i < pathParts.length; i++) {
                if (currentValue === undefined || currentValue === null) return undefined;
                currentValue = currentValue[pathParts[i]];
            }

            return currentValue;
        },

        /**
         * Establece valor anidado usando path con puntos
         *
         * @param {Object} targetObject Objeto destino
         * @param {string} propertyPath Path de la propiedad
         * @param {*} value Valor a establecer
         */
        setNestedValue: function(targetObject, propertyPath, value) {
            var pathParts = propertyPath.split('.');
            var currentObject = targetObject;

            for (var i = 0; i < pathParts.length - 1; i++) {
                if (!currentObject[pathParts[i]]) {
                    currentObject[pathParts[i]] = {};
                }
                currentObject = currentObject[pathParts[i]];
            }

            currentObject[pathParts[pathParts.length - 1]] = value;
        },

        /**
         * Verifica si un elemento tiene overrides para un breakpoint
         *
         * @param {string} elementId ID del elemento
         * @param {string} breakpoint ID del breakpoint
         * @returns {boolean}
         */
        hasOverrides: function(elementId, breakpoint) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return false;

            var element = store.getElementDeep(elementId);
            if (!element || !element.responsive) return false;

            var overrides = element.responsive[breakpoint];
            return overrides && Object.keys(overrides).length > 0;
        },

        /**
         * Obtiene las propiedades que tienen override en un breakpoint
         *
         * @param {string} elementId ID del elemento
         * @param {string} breakpoint ID del breakpoint
         * @returns {Array} Lista de paths de propiedades con override
         */
        getOverriddenProps: function(elementId, breakpoint) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return [];

            var element = store.getElementDeep(elementId);
            if (!element || !element.responsive || !element.responsive[breakpoint]) {
                return [];
            }

            var overriddenProperties = [];
            this.collectPropertyPaths(element.responsive[breakpoint], '', overriddenProperties);
            return overriddenProperties;
        },

        /**
         * Recolecta paths de propiedades recursivamente
         *
         * @param {Object} sourceObject Objeto fuente
         * @param {string} currentPrefix Prefijo actual del path
         * @param {Array} collectedPaths Array donde acumular paths
         */
        collectPropertyPaths: function(sourceObject, currentPrefix, collectedPaths) {
            for (var key in sourceObject) {
                if (sourceObject.hasOwnProperty(key)) {
                    var fullPath = currentPrefix ? currentPrefix + '.' + key : key;

                    if (sourceObject[key] !== null &&
                        typeof sourceObject[key] === 'object' &&
                        !Array.isArray(sourceObject[key])) {
                        this.collectPropertyPaths(sourceObject[key], fullPath, collectedPaths);
                    } else {
                        collectedPaths.push(fullPath);
                    }
                }
            }
        },

        /**
         * Invalida la cache de un elemento
         *
         * @param {string} elementId ID del elemento
         */
        invalidateCache: function(elementId) {
            for (var breakpointId in this.breakpoints) {
                this.styleCache.delete(elementId + '_' + breakpointId);
            }
        },

        /**
         * Limpia toda la cache
         */
        clearCache: function() {
            this.styleCache.clear();
        },

        /**
         * Actualiza el selector de breakpoints en el toolbar
         */
        updateBreakpointSelector: function() {
            var selectorButtons = document.querySelectorAll('.vbp-breakpoint-btn');
            var self = this;

            selectorButtons.forEach(function(buttonElement) {
                var breakpointId = buttonElement.dataset.breakpoint;
                buttonElement.classList.toggle('is-active', breakpointId === self.currentBreakpoint);
            });
        },

        /**
         * Actualiza indicadores de override en elementos
         */
        updateOverrideIndicators: function() {
            var self = this;

            // Limpiar indicadores anteriores
            var existingIndicators = document.querySelectorAll('.vbp-responsive-override-indicator');
            existingIndicators.forEach(function(indicatorElement) {
                indicatorElement.remove();
            });

            // Limpiar clases de override
            var overrideElements = document.querySelectorAll('.vbp-element--has-overrides');
            overrideElements.forEach(function(elementDom) {
                elementDom.classList.remove('vbp-element--has-overrides');
            });

            // Agregar indicadores a elementos con overrides
            var store = window.Alpine && Alpine.store('vbp');
            if (!store || !store.responsive || !store.responsive.highlightOverrides) return;

            this.elementsWithOverrides.clear();

            var allElements = document.querySelectorAll('.vbp-element[data-element-id]');
            allElements.forEach(function(elementDom) {
                var elementId = elementDom.dataset.elementId;
                if (self.hasOverrides(elementId, self.currentBreakpoint)) {
                    self.elementsWithOverrides.add(elementId);
                    elementDom.classList.add('vbp-element--has-overrides');
                    self.addOverrideIndicator(elementDom, elementId);
                }
            });
        },

        /**
         * Agrega indicador visual de override a un elemento
         *
         * @param {HTMLElement} elementDom Elemento DOM
         * @param {string} elementId ID del elemento
         */
        addOverrideIndicator: function(elementDom, elementId) {
            var indicatorElement = document.createElement('div');
            indicatorElement.className = 'vbp-responsive-override-indicator';
            indicatorElement.setAttribute('data-element-id', elementId);
            indicatorElement.innerHTML = this.getBreakpointIcon(this.currentBreakpoint);
            indicatorElement.title = 'Este elemento tiene cambios para ' +
                this.breakpoints[this.currentBreakpoint].label;

            elementDom.appendChild(indicatorElement);
        },

        /**
         * Inicializa el handle de resize del canvas
         */
        initCanvasResizeHandle: function() {
            var self = this;
            var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
            if (!canvasWrapper) return;

            // Crear handle si no existe
            var existingHandle = canvasWrapper.querySelector('.vbp-canvas-resize-handle');
            if (existingHandle) return;

            var handleElement = document.createElement('div');
            handleElement.className = 'vbp-canvas-resize-handle';
            handleElement.innerHTML = '<div class="vbp-canvas-resize-handle__grip"></div>';

            var canvas = canvasWrapper.querySelector('.vbp-canvas');
            if (canvas) {
                canvas.appendChild(handleElement);
            }

            var isDragging = false;
            var startX = 0;
            var startWidth = 0;

            handleElement.addEventListener('mousedown', function(event) {
                isDragging = true;
                startX = event.clientX;
                startWidth = canvas.offsetWidth;
                document.body.classList.add('vbp-resizing-canvas');
                event.preventDefault();
            });

            document.addEventListener('mousemove', function(event) {
                if (!isDragging) return;

                var deltaX = event.clientX - startX;
                var newWidth = Math.max(320, startWidth + deltaX * 2); // *2 porque el canvas esta centrado
                newWidth = Math.min(newWidth, 1920);

                canvas.style.width = newWidth + 'px';
                canvas.style.maxWidth = newWidth + 'px';

                self.showResizeTooltip(newWidth);
                self.snapToBreakpointRuler(newWidth);
            });

            document.addEventListener('mouseup', function() {
                if (!isDragging) return;
                isDragging = false;
                document.body.classList.remove('vbp-resizing-canvas');
                self.hideResizeTooltip();

                var finalWidth = canvas.offsetWidth;
                self.canvasWidth = finalWidth;

                // Detectar si coincide con un breakpoint
                var matchedBreakpoint = self.detectBreakpoint(finalWidth);
                if (matchedBreakpoint !== self.currentBreakpoint) {
                    self.showBreakpointSuggestion(matchedBreakpoint, finalWidth);
                }
            });
        },

        /**
         * Muestra tooltip de resize con el ancho actual
         *
         * @param {number} width Ancho actual
         */
        showResizeTooltip: function(width) {
            var tooltipElement = document.getElementById('vbp-canvas-resize-tooltip');
            if (!tooltipElement) {
                tooltipElement = document.createElement('div');
                tooltipElement.id = 'vbp-canvas-resize-tooltip';
                tooltipElement.className = 'vbp-canvas-resize-tooltip';
                document.body.appendChild(tooltipElement);
            }

            var detectedBreakpoint = this.detectBreakpoint(width);
            var breakpointConfig = this.breakpoints[detectedBreakpoint];

            tooltipElement.innerHTML = '<span class="vbp-canvas-resize-tooltip__width">' +
                width + 'px</span><span class="vbp-canvas-resize-tooltip__breakpoint">' +
                breakpointConfig.label + '</span>';
            tooltipElement.classList.add('is-visible');
        },

        /**
         * Oculta tooltip de resize
         */
        hideResizeTooltip: function() {
            var tooltipElement = document.getElementById('vbp-canvas-resize-tooltip');
            if (tooltipElement) {
                tooltipElement.classList.remove('is-visible');
            }
        },

        /**
         * Snap visual a marcas del ruler de breakpoints
         *
         * @param {number} currentWidth Ancho actual
         */
        snapToBreakpointRuler: function(currentWidth) {
            var snapThreshold = 10;
            var highlightedBreakpoint = null;

            for (var breakpointId in this.breakpoints) {
                var breakpointConfig = this.breakpoints[breakpointId];
                if (Math.abs(currentWidth - breakpointConfig.canvasWidth) < snapThreshold) {
                    highlightedBreakpoint = breakpointId;
                    break;
                }
            }

            // Actualizar ruler highlights
            var rulerMarks = document.querySelectorAll('.vbp-breakpoint-ruler__mark');
            rulerMarks.forEach(function(markElement) {
                markElement.classList.toggle('is-snapping',
                    markElement.dataset.breakpoint === highlightedBreakpoint);
            });
        },

        /**
         * Inicializa el ruler de breakpoints
         */
        initBreakpointRuler: function() {
            var canvasArea = document.querySelector('.vbp-canvas-area');
            if (!canvasArea) return;

            var existingRuler = canvasArea.querySelector('.vbp-breakpoint-ruler');
            if (existingRuler) return;

            var rulerElement = document.createElement('div');
            rulerElement.className = 'vbp-breakpoint-ruler';

            var rulerContent = '<div class="vbp-breakpoint-ruler__track">';

            for (var breakpointId in this.breakpoints) {
                var breakpointConfig = this.breakpoints[breakpointId];
                var positionPercent = (breakpointConfig.canvasWidth / 1920) * 100;

                rulerContent += '<div class="vbp-breakpoint-ruler__mark" ' +
                    'data-breakpoint="' + breakpointId + '" ' +
                    'style="left: ' + positionPercent + '%">' +
                    '<span class="vbp-breakpoint-ruler__label">' +
                    breakpointConfig.shortLabel + '</span>' +
                    '<span class="vbp-breakpoint-ruler__width">' +
                    breakpointConfig.canvasWidth + '</span>' +
                    '</div>';
            }

            rulerContent += '</div>';
            rulerElement.innerHTML = rulerContent;

            canvasArea.insertBefore(rulerElement, canvasArea.firstChild);

            // Bind clicks en marcas
            var self = this;
            rulerElement.querySelectorAll('.vbp-breakpoint-ruler__mark').forEach(function(markElement) {
                markElement.addEventListener('click', function() {
                    var breakpointId = this.dataset.breakpoint;
                    self.setBreakpoint(breakpointId);
                });
            });
        },

        /**
         * Actualiza el ruler de breakpoints
         */
        updateBreakpointRuler: function() {
            var rulerMarks = document.querySelectorAll('.vbp-breakpoint-ruler__mark');
            var self = this;

            rulerMarks.forEach(function(markElement) {
                markElement.classList.toggle('is-active',
                    markElement.dataset.breakpoint === self.currentBreakpoint);
            });
        },

        /**
         * Genera CSS con media queries para un elemento
         *
         * @param {string} elementId ID del elemento
         * @param {string} selector Selector CSS del elemento
         * @returns {string} CSS generado
         */
        generateResponsiveCSS: function(elementId, selector) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return '';

            var element = store.getElementDeep(elementId);
            if (!element || !element.responsive) return '';

            var cssOutput = '';
            var breakpointOrder = this.cssOrderMode === 'desktop-first'
                ? BREAKPOINT_ORDER_DESKTOP_FIRST
                : BREAKPOINT_ORDER_MOBILE_FIRST;

            for (var i = 0; i < breakpointOrder.length; i++) {
                var breakpointId = breakpointOrder[i];
                if (breakpointId === 'desktop') continue;

                var overrides = element.responsive[breakpointId];
                if (!overrides || Object.keys(overrides).length === 0) continue;

                var breakpointConfig = this.breakpoints[breakpointId];
                var cssProperties = this.stylesToCSS(overrides);

                if (cssProperties) {
                    cssOutput += '\n' + breakpointConfig.cssMediaQuery + ' {\n';
                    cssOutput += '  ' + selector + ' {\n';
                    cssOutput += cssProperties;
                    cssOutput += '  }\n';
                    cssOutput += '}\n';
                }
            }

            return cssOutput;
        },

        /**
         * Convierte objeto de estilos a propiedades CSS
         *
         * @param {Object} styleObject Objeto de estilos
         * @returns {string} Propiedades CSS
         */
        stylesToCSS: function(styleObject) {
            var cssLines = [];

            // Mapeo de propiedades JS a CSS
            var propertyMap = {
                fontSize: 'font-size',
                lineHeight: 'line-height',
                letterSpacing: 'letter-spacing',
                textAlign: 'text-align',
                flexDirection: 'flex-direction',
                justifyContent: 'justify-content',
                alignItems: 'align-items',
                gridTemplateColumns: 'grid-template-columns',
                minWidth: 'min-width',
                maxWidth: 'max-width',
                minHeight: 'min-height',
                maxHeight: 'max-height',
                marginTop: 'margin-top',
                marginRight: 'margin-right',
                marginBottom: 'margin-bottom',
                marginLeft: 'margin-left',
                paddingTop: 'padding-top',
                paddingRight: 'padding-right',
                paddingBottom: 'padding-bottom',
                paddingLeft: 'padding-left',
                zIndex: 'z-index',
                flexGrow: 'flex-grow',
                flexShrink: 'flex-shrink'
            };

            this.flattenStyles(styleObject, '', function(propertyPath, value) {
                // Ignorar propiedades especiales
                if (propertyPath === 'hidden' || propertyPath.startsWith('_')) return;

                // Convertir nombre de propiedad
                var cssProperty = propertyMap[propertyPath] || propertyPath.replace(/([A-Z])/g, '-$1').toLowerCase();

                // Manejar hidden especialmente
                if (propertyPath === 'hidden' && value === true) {
                    cssLines.push('    display: none;');
                } else if (value !== null && value !== undefined && value !== '') {
                    cssLines.push('    ' + cssProperty + ': ' + value + ';');
                }
            });

            return cssLines.join('\n') + '\n';
        },

        /**
         * Aplana objeto de estilos a pares propiedad-valor
         *
         * @param {Object} styleObject Objeto de estilos
         * @param {string} currentPrefix Prefijo actual
         * @param {Function} callback Callback con (path, value)
         */
        flattenStyles: function(styleObject, currentPrefix, callback) {
            for (var key in styleObject) {
                if (styleObject.hasOwnProperty(key)) {
                    var value = styleObject[key];
                    var path = currentPrefix ? currentPrefix + '.' + key : key;

                    if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
                        // Para ciertos objetos anidados, extraer valores directamente
                        if (key === 'spacing') {
                            this.flattenStyles(value, '', callback);
                        } else {
                            this.flattenStyles(value, path, callback);
                        }
                    } else {
                        callback(path, value);
                    }
                }
            }
        },

        /**
         * Obtiene comparacion de diferencias entre breakpoints
         *
         * @param {string} elementId ID del elemento
         * @param {string} breakpointA Primer breakpoint
         * @param {string} breakpointB Segundo breakpoint
         * @returns {Object} Diferencias entre breakpoints
         */
        getDifferences: function(elementId, breakpointA, breakpointB) {
            var originalBreakpoint = this.currentBreakpoint;

            this.currentBreakpoint = breakpointA;
            var propsA = this.getEffectiveProps(elementId);

            this.currentBreakpoint = breakpointB;
            var propsB = this.getEffectiveProps(elementId);

            this.currentBreakpoint = originalBreakpoint;

            var differences = {
                changed: [],
                addedInB: [],
                removedInB: []
            };

            // Encontrar diferencias
            var allKeys = new Set([
                ...Object.keys(propsA),
                ...Object.keys(propsB)
            ]);

            var self = this;
            allKeys.forEach(function(key) {
                var valueA = JSON.stringify(propsA[key]);
                var valueB = JSON.stringify(propsB[key]);

                if (valueA === undefined && valueB !== undefined) {
                    differences.addedInB.push(key);
                } else if (valueA !== undefined && valueB === undefined) {
                    differences.removedInB.push(key);
                } else if (valueA !== valueB) {
                    differences.changed.push({
                        property: key,
                        valueA: propsA[key],
                        valueB: propsB[key]
                    });
                }
            });

            return differences;
        },

        /**
         * Obtiene configuracion de breakpoints
         *
         * @returns {Object}
         */
        getBreakpoints: function() {
            return this.breakpoints;
        },

        /**
         * Obtiene el breakpoint actual
         *
         * @returns {string}
         */
        getCurrentBreakpoint: function() {
            return this.currentBreakpoint;
        },

        /**
         * Obtiene el ancho del canvas actual
         *
         * @returns {number}
         */
        getCanvasWidth: function() {
            return this.canvasWidth;
        }
    };

    /**
     * Componente Alpine para el panel de responsive variants en el inspector
     */
    window.vbpResponsivePanel = function() {
        return {
            /**
             * Breakpoint activo actualmente
             */
            get currentBreakpoint() {
                return window.VBPResponsiveVariants.getCurrentBreakpoint();
            },

            /**
             * Configuracion de breakpoints
             */
            get breakpoints() {
                return window.VBPResponsiveVariants.getBreakpoints();
            },

            /**
             * Ancho del canvas
             */
            get canvasWidth() {
                return window.VBPResponsiveVariants.getCanvasWidth();
            },

            /**
             * Elemento seleccionado
             */
            get selectedElement() {
                var store = Alpine.store('vbp');
                if (store.selection.elementIds.length === 1) {
                    return store.getElementDeep(store.selection.elementIds[0]);
                }
                return null;
            },

            /**
             * Verifica si el breakpoint actual tiene overrides
             */
            get hasOverridesForCurrentBreakpoint() {
                if (!this.selectedElement) return false;
                return window.VBPResponsiveVariants.hasOverrides(
                    this.selectedElement.id,
                    this.currentBreakpoint
                );
            },

            /**
             * Obtiene lista de propiedades con override
             */
            get overriddenProperties() {
                if (!this.selectedElement) return [];
                return window.VBPResponsiveVariants.getOverriddenProps(
                    this.selectedElement.id,
                    this.currentBreakpoint
                );
            },

            /**
             * Obtiene breakpoints con overrides para el elemento
             */
            get breakpointsWithOverrides() {
                if (!this.selectedElement) return [];

                var breakpointsWithChanges = [];
                var elementId = this.selectedElement.id;

                for (var breakpointId in this.breakpoints) {
                    if (breakpointId !== 'desktop' &&
                        window.VBPResponsiveVariants.hasOverrides(elementId, breakpointId)) {
                        breakpointsWithChanges.push(breakpointId);
                    }
                }

                return breakpointsWithChanges;
            },

            /**
             * Cambia el breakpoint activo
             *
             * @param {string} breakpoint ID del breakpoint
             */
            setBreakpoint: function(breakpoint) {
                window.VBPResponsiveVariants.setBreakpoint(breakpoint);
            },

            /**
             * Limpia overrides del breakpoint actual
             */
            clearCurrentOverrides: function() {
                if (!this.selectedElement) return;
                window.VBPResponsiveVariants.clearAllOverrides(
                    this.selectedElement.id,
                    this.currentBreakpoint
                );
            },

            /**
             * Copia layout del breakpoint base al actual
             */
            copyFromDesktop: function() {
                if (!this.selectedElement || this.currentBreakpoint === 'desktop') return;
                window.VBPResponsiveVariants.copyLayout(
                    this.selectedElement.id,
                    'desktop',
                    this.currentBreakpoint
                );
            },

            /**
             * Copia layout del breakpoint actual a todos los demas
             */
            copyToAllBreakpoints: function() {
                if (!this.selectedElement) return;

                var self = this;
                for (var breakpointId in this.breakpoints) {
                    if (breakpointId !== this.currentBreakpoint) {
                        window.VBPResponsiveVariants.copyLayout(
                            self.selectedElement.id,
                            self.currentBreakpoint,
                            breakpointId
                        );
                    }
                }
            },

            /**
             * Obtiene icono del breakpoint
             *
             * @param {string} breakpoint ID del breakpoint
             * @returns {string} HTML del icono
             */
            getBreakpointIcon: function(breakpoint) {
                return window.VBPResponsiveVariants.getBreakpointIcon(breakpoint);
            },

            /**
             * Verifica si una propiedad tiene override en el breakpoint actual
             *
             * @param {string} propertyPath Path de la propiedad
             * @returns {boolean}
             */
            hasOverrideFor: function(propertyPath) {
                return this.overriddenProperties.indexOf(propertyPath) !== -1;
            },

            /**
             * Limpia override de una propiedad especifica
             *
             * @param {string} propertyPath Path de la propiedad
             */
            clearPropertyOverride: function(propertyPath) {
                if (!this.selectedElement) return;
                window.VBPResponsiveVariants.clearOverride(
                    this.selectedElement.id,
                    this.currentBreakpoint,
                    propertyPath
                );
            },

            /**
             * Verifica si el breakpoint actual es desktop (base)
             */
            get isBaseBreakpoint() {
                return this.currentBreakpoint === 'desktop';
            }
        };
    };

    // Inicializar cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.VBPResponsiveVariants.init();
        });
    } else {
        window.VBPResponsiveVariants.init();
    }

})();
