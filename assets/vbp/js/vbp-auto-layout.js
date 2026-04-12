/**
 * VBP Auto Layout - Sistema de Auto Layout nivel Figma
 *
 * Implementa un sistema de layout automático compatible con Figma que permite:
 * - Layouts flexibles (vertical/horizontal)
 * - Spacing entre items
 * - Padding configurable
 * - Sizing (hug/fixed/fill)
 * - Alineación primaria y secundaria
 * - Wrap y reordenamiento
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

(function() {
    'use strict';

    /**
     * Constantes de configuración
     */
    var AUTO_LAYOUT_COLOR = '#8b5cf6';
    var AUTO_LAYOUT_COLOR_ACTIVE = '#7c3aed';
    var AUTO_LAYOUT_INDICATOR_COLOR = 'rgba(139, 92, 246, 0.6)';

    /**
     * Presets de Auto Layout predefinidos
     */
    var AUTO_LAYOUT_PRESETS = {
        'stack-vertical': {
            direction: 'vertical',
            spacing: 16,
            spacingMode: 'packed',
            primarySizing: 'hug',
            counterSizing: 'hug',
            primaryAlign: 'start',
            counterAlign: 'stretch',
            padding: { top: 0, right: 0, bottom: 0, left: 0 },
            label: 'Stack Vertical',
            icon: '&#x2B07;'
        },
        'stack-horizontal': {
            direction: 'horizontal',
            spacing: 8,
            spacingMode: 'packed',
            primarySizing: 'hug',
            counterSizing: 'hug',
            primaryAlign: 'start',
            counterAlign: 'center',
            padding: { top: 0, right: 0, bottom: 0, left: 0 },
            label: 'Stack Horizontal',
            icon: '&#x27A1;'
        },
        'navbar': {
            direction: 'horizontal',
            spacing: 0,
            spacingMode: 'space-between',
            primarySizing: 'fill',
            counterSizing: 'hug',
            primaryAlign: 'start',
            counterAlign: 'center',
            padding: { top: 12, right: 24, bottom: 12, left: 24 },
            label: 'Navbar',
            icon: '&#x2630;'
        },
        'card': {
            direction: 'vertical',
            spacing: 12,
            spacingMode: 'packed',
            primarySizing: 'hug',
            counterSizing: 'fixed',
            primaryAlign: 'start',
            counterAlign: 'stretch',
            padding: { top: 16, right: 16, bottom: 16, left: 16 },
            label: 'Card',
            icon: '&#x1F4C4;'
        },
        'grid-2col': {
            direction: 'horizontal',
            spacing: 16,
            spacingMode: 'packed',
            primarySizing: 'fill',
            counterSizing: 'hug',
            primaryAlign: 'start',
            counterAlign: 'start',
            wrap: true,
            wrapSpacing: 16,
            padding: { top: 0, right: 0, bottom: 0, left: 0 },
            label: 'Grid 2 Cols',
            icon: '&#x25A6;'
        },
        'centered': {
            direction: 'vertical',
            spacing: 16,
            spacingMode: 'packed',
            primarySizing: 'hug',
            counterSizing: 'hug',
            primaryAlign: 'center',
            counterAlign: 'center',
            padding: { top: 24, right: 24, bottom: 24, left: 24 },
            label: 'Centrado',
            icon: '&#x2B55;'
        },
        'sidebar-left': {
            direction: 'horizontal',
            spacing: 24,
            spacingMode: 'packed',
            primarySizing: 'fill',
            counterSizing: 'fill',
            primaryAlign: 'start',
            counterAlign: 'stretch',
            padding: { top: 0, right: 0, bottom: 0, left: 0 },
            label: 'Sidebar Izq',
            icon: '&#x25E7;'
        },
        'footer': {
            direction: 'horizontal',
            spacing: 32,
            spacingMode: 'space-between',
            primarySizing: 'fill',
            counterSizing: 'hug',
            primaryAlign: 'start',
            counterAlign: 'start',
            wrap: true,
            wrapSpacing: 24,
            padding: { top: 48, right: 24, bottom: 48, left: 24 },
            label: 'Footer',
            icon: '&#x2B13;'
        }
    };

    /**
     * Opciones de spacing mode
     */
    var SPACING_MODES = {
        'packed': { label: 'Packed', css: 'flex-start' },
        'space-between': { label: 'Space Between', css: 'space-between' },
        'space-around': { label: 'Space Around', css: 'space-around' },
        'space-evenly': { label: 'Space Evenly', css: 'space-evenly' }
    };

    /**
     * Opciones de sizing
     */
    var SIZING_OPTIONS = {
        'hug': { label: 'Hug', icon: '&#x21B9;', description: 'Ajustar al contenido' },
        'fixed': { label: 'Fixed', icon: '&#x1F4CF;', description: 'Tamaño fijo' },
        'fill': { label: 'Fill', icon: '&#x2194;', description: 'Llenar espacio disponible' }
    };

    /**
     * Opciones de alineación
     */
    var ALIGNMENT_OPTIONS = {
        primary: {
            'start': { label: 'Inicio', icon: '&#x21E4;' },
            'center': { label: 'Centro', icon: '&#x21C6;' },
            'end': { label: 'Fin', icon: '&#x21E5;' }
        },
        counter: {
            'start': { label: 'Inicio', icon: '&#x21E4;' },
            'center': { label: 'Centro', icon: '&#x21C6;' },
            'end': { label: 'Fin', icon: '&#x21E5;' },
            'stretch': { label: 'Estirar', icon: '&#x2195;' },
            'baseline': { label: 'Baseline', icon: '&#x2013;' }
        }
    };

    /**
     * Sistema de Auto Layout
     */
    window.VBPAutoLayout = {
        /**
         * Estado del sistema
         */
        enabled: true,

        /**
         * Control de inicialización
         */
        _initialized: false,

        /**
         * Referencias a event handlers para cleanup
         */
        _eventHandlers: {},

        /**
         * Contenedor de indicadores visuales
         */
        indicatorsContainer: null,

        /**
         * Cache de layouts
         */
        layoutCache: new Map(),

        /**
         * Observer de cambios de tamaño
         */
        resizeObserver: null,

        /**
         * Inicializa el sistema de Auto Layout
         */
        init: function() {
            if (this._initialized) return;
            this._initialized = true;

            this.createIndicatorsContainer();
            this.bindEvents();
            this.initResizeObserver();
            this.registerKeyboardShortcuts();
        },

        /**
         * Destruir y limpiar recursos
         */
        destroy: function() {
            // Remover event listeners
            if (this._eventHandlers.selectionChanged) {
                document.removeEventListener('vbp:selection:changed', this._eventHandlers.selectionChanged);
            }
            if (this._eventHandlers.elementUpdated) {
                document.removeEventListener('vbp:element:updated', this._eventHandlers.elementUpdated);
            }
            if (this._eventHandlers.canvasResized) {
                document.removeEventListener('vbp:canvas:resized', this._eventHandlers.canvasResized);
            }
            if (this._eventHandlers.breakpointChanged) {
                document.removeEventListener('vbp:breakpoint:changed', this._eventHandlers.breakpointChanged);
            }
            if (this._eventHandlers.dragStart) {
                document.removeEventListener('vbp:drag:start', this._eventHandlers.dragStart);
            }
            if (this._eventHandlers.dragOver) {
                document.removeEventListener('vbp:drag:over', this._eventHandlers.dragOver);
            }
            if (this._eventHandlers.dragEnd) {
                document.removeEventListener('vbp:drag:end', this._eventHandlers.dragEnd);
            }
            if (this._eventHandlers.keydown) {
                document.removeEventListener('keydown', this._eventHandlers.keydown);
            }
            this._eventHandlers = {};

            // Desconectar resize observer
            if (this.resizeObserver) {
                this.resizeObserver.disconnect();
                this.resizeObserver = null;
            }

            // Limpiar indicadores
            this.clearIndicators();

            // Limpiar cache
            this.layoutCache.clear();

            this._initialized = false;
        },

        /**
         * Crea el contenedor para indicadores visuales
         */
        createIndicatorsContainer: function() {
            var existingContainer = document.getElementById('vbp-auto-layout-indicators');
            if (existingContainer) {
                this.indicatorsContainer = existingContainer;
                return;
            }

            var containerElement = document.createElement('div');
            containerElement.className = 'vbp-auto-layout-indicators';
            containerElement.id = 'vbp-auto-layout-indicators';

            var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
            if (canvasWrapper) {
                canvasWrapper.appendChild(containerElement);
                this.indicatorsContainer = containerElement;
            }
        },

        /**
         * Vincula eventos necesarios
         */
        bindEvents: function() {
            var self = this;

            // Guardar referencias para cleanup
            this._eventHandlers.selectionChanged = function() {
                self.updateIndicators();
            };
            this._eventHandlers.elementUpdated = function(event) {
                if (event.detail && event.detail.changes) {
                    self.handleElementUpdate(event.detail.id, event.detail.changes);
                }
            };
            this._eventHandlers.canvasResized = function() {
                self.recalculateAllLayouts();
            };
            this._eventHandlers.breakpointChanged = function() {
                self.recalculateAllLayouts();
            };
            this._eventHandlers.dragStart = function(event) {
                self.handleDragStart(event.detail);
            };
            this._eventHandlers.dragOver = function(event) {
                self.handleDragOver(event.detail);
            };
            this._eventHandlers.dragEnd = function(event) {
                self.handleDragEnd(event.detail);
            };

            // Registrar eventos
            document.addEventListener('vbp:selection:changed', this._eventHandlers.selectionChanged);
            document.addEventListener('vbp:element:updated', this._eventHandlers.elementUpdated);
            document.addEventListener('vbp:canvas:resized', this._eventHandlers.canvasResized);
            document.addEventListener('vbp:breakpoint:changed', this._eventHandlers.breakpointChanged);
            document.addEventListener('vbp:drag:start', this._eventHandlers.dragStart);
            document.addEventListener('vbp:drag:over', this._eventHandlers.dragOver);
            document.addEventListener('vbp:drag:end', this._eventHandlers.dragEnd);
        },

        /**
         * Inicializa el observer de redimensionamiento
         */
        initResizeObserver: function() {
            var self = this;

            if (typeof ResizeObserver === 'undefined') {
                return;
            }

            this.resizeObserver = new ResizeObserver(function(entries) {
                entries.forEach(function(entry) {
                    var element = entry.target;
                    var elementId = element.dataset.elementId;
                    if (elementId && self.hasAutoLayout(elementId)) {
                        self.recalculateLayout(elementId);
                    }
                });
            });
        },

        /**
         * Registra atajos de teclado
         */
        registerKeyboardShortcuts: function() {
            var self = this;

            this._eventHandlers.keydown = function(event) {
                // Solo si hay un elemento seleccionado
                var store = window.Alpine && Alpine.store('vbp');
                if (!store || !store.selection || !store.selection.elementIds || store.selection.elementIds.length !== 1) return;

                var elementId = store.selection.elementIds[0];

                // Shift+A: Agregar auto layout
                if (event.shiftKey && !event.altKey && event.key === 'A') {
                    event.preventDefault();
                    if (!self.hasAutoLayout(elementId)) {
                        self.addAutoLayout(elementId);
                    }
                }

                // Alt+Shift+A: Remover auto layout
                if (event.shiftKey && event.altKey && event.key === 'A') {
                    event.preventDefault();
                    if (self.hasAutoLayout(elementId)) {
                        self.removeAutoLayout(elementId);
                    }
                }

                // Tab: Cambiar dirección (solo si tiene auto layout)
                if (event.key === 'Tab' && !event.shiftKey && self.hasAutoLayout(elementId)) {
                    var autoLayoutConfig = self.getAutoLayout(elementId);
                    if (autoLayoutConfig && document.activeElement.tagName !== 'INPUT') {
                        event.preventDefault();
                        var newDirection = autoLayoutConfig.direction === 'vertical' ? 'horizontal' : 'vertical';
                        self.updateAutoLayout(elementId, { direction: newDirection });
                    }
                }

                // [ y ]: Ajustar spacing
                if (self.hasAutoLayout(elementId)) {
                    var autoLayoutConfig = self.getAutoLayout(elementId);
                    if (autoLayoutConfig) {
                        if (event.key === '[') {
                            event.preventDefault();
                            var newSpacing = Math.max(0, autoLayoutConfig.spacing - (event.shiftKey ? 8 : 4));
                            self.updateAutoLayout(elementId, { spacing: newSpacing });
                        }
                        if (event.key === ']') {
                            event.preventDefault();
                            var newSpacing = autoLayoutConfig.spacing + (event.shiftKey ? 8 : 4);
                            self.updateAutoLayout(elementId, { spacing: newSpacing });
                        }
                    }
                }
            };

            document.addEventListener('keydown', this._eventHandlers.keydown);
        },

        /**
         * Verifica si un elemento tiene auto layout
         *
         * @param {string} elementId ID del elemento
         * @returns {boolean}
         */
        hasAutoLayout: function(elementId) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return false;

            var element = store.getElementDeep(elementId);
            return element && element.autoLayout && element.autoLayout.enabled;
        },

        /**
         * Obtiene la configuración de auto layout
         *
         * @param {string} elementId ID del elemento
         * @returns {Object|null}
         */
        getAutoLayout: function(elementId) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return null;

            var element = store.getElementDeep(elementId);
            if (!element || !element.autoLayout) return null;

            return element.autoLayout;
        },

        /**
         * Obtiene la configuración de layoutChild
         *
         * @param {string} elementId ID del elemento hijo
         * @returns {Object|null}
         */
        getLayoutChild: function(elementId) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return null;

            var element = store.getElementDeep(elementId);
            if (!element || !element.layoutChild) return null;

            return element.layoutChild;
        },

        /**
         * Crea la configuración por defecto de auto layout
         *
         * @returns {Object}
         */
        getDefaultAutoLayout: function() {
            return {
                enabled: true,
                direction: 'vertical',
                spacing: 16,
                spacingMode: 'packed',
                padding: {
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0
                },
                primarySizing: 'hug',
                counterSizing: 'hug',
                primaryAlign: 'start',
                counterAlign: 'start',
                wrap: false,
                wrapSpacing: 16,
                reverse: false,
                clipContent: true,
                absoluteChildren: []
            };
        },

        /**
         * Crea la configuración por defecto de layout child
         *
         * @returns {Object}
         */
        getDefaultLayoutChild: function() {
            return {
                sizing: 'hug',
                fillRatio: 1,
                minWidth: null,
                maxWidth: null,
                minHeight: null,
                maxHeight: null,
                alignSelf: 'auto',
                absolute: false,
                absolutePosition: { top: 0, right: null, bottom: null, left: 0 }
            };
        },

        /**
         * Agrega auto layout a un elemento
         *
         * @param {string} elementId ID del elemento
         * @param {Object} config Configuración inicial (opcional)
         */
        addAutoLayout: function(elementId, config) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep(elementId);
            if (!element) return;

            var autoLayoutConfig = Object.assign({}, this.getDefaultAutoLayout(), config || {});

            store.updateElement(elementId, { autoLayout: autoLayoutConfig });

            // Inicializar layoutChild en hijos
            if (element.children && element.children.length > 0) {
                var self = this;
                element.children.forEach(function(child) {
                    if (!child.layoutChild) {
                        store.updateElement(child.id, { layoutChild: self.getDefaultLayoutChild() });
                    }
                });
            }

            // Observar redimensionamiento
            var domElement = document.querySelector('[data-element-id="' + elementId + '"]');
            if (domElement && this.resizeObserver) {
                this.resizeObserver.observe(domElement);
            }

            this.applyAutoLayoutStyles(elementId);
            this.updateIndicators();

            // Dispatch event
            document.dispatchEvent(new CustomEvent('vbp:autolayout:added', {
                detail: { elementId: elementId, config: autoLayoutConfig }
            }));
        },

        /**
         * Remueve auto layout de un elemento
         *
         * @param {string} elementId ID del elemento
         */
        removeAutoLayout: function(elementId) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep(elementId);
            if (!element) return;

            // Remover configuración
            store.updateElement(elementId, { autoLayout: null });

            // Remover layoutChild de hijos
            if (element.children && element.children.length > 0) {
                element.children.forEach(function(child) {
                    store.updateElement(child.id, { layoutChild: null });
                });
            }

            // Detener observación
            var domElement = document.querySelector('[data-element-id="' + elementId + '"]');
            if (domElement && this.resizeObserver) {
                this.resizeObserver.unobserve(domElement);
            }

            this.removeAutoLayoutStyles(elementId);
            this.updateIndicators();

            // Dispatch event
            document.dispatchEvent(new CustomEvent('vbp:autolayout:removed', {
                detail: { elementId: elementId }
            }));
        },

        /**
         * Actualiza la configuración de auto layout
         *
         * @param {string} elementId ID del elemento
         * @param {Object} changes Cambios a aplicar
         */
        updateAutoLayout: function(elementId, changes) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep(elementId);
            if (!element || !element.autoLayout) return;

            var updatedConfig = Object.assign({}, element.autoLayout, changes);
            store.updateElement(elementId, { autoLayout: updatedConfig });

            this.applyAutoLayoutStyles(elementId);
            this.updateIndicators();

            // Dispatch event
            document.dispatchEvent(new CustomEvent('vbp:autolayout:updated', {
                detail: { elementId: elementId, changes: changes }
            }));
        },

        /**
         * Actualiza la configuración de layout child
         *
         * @param {string} elementId ID del elemento hijo
         * @param {Object} changes Cambios a aplicar
         */
        updateLayoutChild: function(elementId, changes) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep(elementId);
            if (!element) return;

            var currentLayoutChild = element.layoutChild || this.getDefaultLayoutChild();
            var updatedConfig = Object.assign({}, currentLayoutChild, changes);
            store.updateElement(elementId, { layoutChild: updatedConfig });

            // Recalcular layout del padre
            var parentElement = this.findParentWithAutoLayout(elementId);
            if (parentElement) {
                this.applyAutoLayoutStyles(parentElement.id);
            }

            this.updateIndicators();
        },

        /**
         * Encuentra el elemento padre que tiene auto layout
         *
         * @param {string} elementId ID del elemento hijo
         * @returns {Object|null}
         */
        findParentWithAutoLayout: function(elementId) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return null;

            // Buscar en el árbol de elementos
            var searchInChildren = function(elements, targetId) {
                for (var elementIndex = 0; elementIndex < elements.length; elementIndex++) {
                    var element = elements[elementIndex];
                    if (element.children) {
                        for (var childIndex = 0; childIndex < element.children.length; childIndex++) {
                            if (element.children[childIndex].id === targetId) {
                                if (element.autoLayout && element.autoLayout.enabled) {
                                    return element;
                                }
                            }
                        }
                        var found = searchInChildren(element.children, targetId);
                        if (found) return found;
                    }
                }
                return null;
            };

            return searchInChildren(store.elements, elementId);
        },

        /**
         * Aplica un preset de auto layout
         *
         * @param {string} elementId ID del elemento
         * @param {string} presetName Nombre del preset
         */
        applyPreset: function(elementId, presetName) {
            var preset = AUTO_LAYOUT_PRESETS[presetName];
            if (!preset) return;

            var config = Object.assign({}, this.getDefaultAutoLayout(), preset);
            delete config.label;
            delete config.icon;

            if (this.hasAutoLayout(elementId)) {
                this.updateAutoLayout(elementId, config);
            } else {
                this.addAutoLayout(elementId, config);
            }
        },

        /**
         * Aplica los estilos CSS de auto layout
         *
         * @param {string} elementId ID del elemento
         */
        applyAutoLayoutStyles: function(elementId) {
            var domElement = document.querySelector('[data-element-id="' + elementId + '"]');
            if (!domElement) return;

            var autoLayoutConfig = this.getAutoLayout(elementId);
            if (!autoLayoutConfig || !autoLayoutConfig.enabled) {
                this.removeAutoLayoutStyles(elementId);
                return;
            }

            var styles = this.generateCSSForAutoLayout(autoLayoutConfig);

            // Aplicar estilos al contenedor
            Object.keys(styles.container).forEach(function(prop) {
                domElement.style[prop] = styles.container[prop];
            });

            // Marcar como auto-layout
            domElement.classList.add('vbp-has-auto-layout');
            domElement.dataset.autoLayoutDirection = autoLayoutConfig.direction;

            // Aplicar estilos a los hijos
            var store = window.Alpine && Alpine.store('vbp');
            var element = store.getElementDeep(elementId);
            var self = this;

            if (element && element.children) {
                element.children.forEach(function(child) {
                    var childDom = document.querySelector('[data-element-id="' + child.id + '"]');
                    if (!childDom) return;

                    var layoutChild = child.layoutChild || self.getDefaultLayoutChild();

                    // Si es absoluto, sacarlo del flujo
                    if (layoutChild.absolute || (autoLayoutConfig.absoluteChildren && autoLayoutConfig.absoluteChildren.indexOf(child.id) !== -1)) {
                        childDom.style.position = 'absolute';
                        if (layoutChild.absolutePosition) {
                            if (layoutChild.absolutePosition.top !== null) childDom.style.top = layoutChild.absolutePosition.top + 'px';
                            if (layoutChild.absolutePosition.right !== null) childDom.style.right = layoutChild.absolutePosition.right + 'px';
                            if (layoutChild.absolutePosition.bottom !== null) childDom.style.bottom = layoutChild.absolutePosition.bottom + 'px';
                            if (layoutChild.absolutePosition.left !== null) childDom.style.left = layoutChild.absolutePosition.left + 'px';
                        }
                        childDom.classList.add('vbp-auto-layout-absolute');
                        return;
                    }

                    var childStyles = self.generateCSSForChild(layoutChild, autoLayoutConfig);
                    Object.keys(childStyles).forEach(function(prop) {
                        childDom.style[prop] = childStyles[prop];
                    });

                    childDom.classList.add('vbp-auto-layout-child');
                    childDom.classList.remove('vbp-auto-layout-absolute');
                });
            }

            // Guardar en cache
            this.layoutCache.set(elementId, {
                config: autoLayoutConfig,
                styles: styles
            });
        },

        /**
         * Genera CSS para el contenedor de auto layout
         *
         * @param {Object} config Configuración de auto layout
         * @returns {Object}
         */
        generateCSSForAutoLayout: function(config) {
            var padding = config.padding || { top: 0, right: 0, bottom: 0, left: 0 };
            var containerStyles = {
                display: 'flex',
                flexDirection: config.direction === 'vertical' ? 'column' : 'row',
                gap: (config.spacing || 0) + 'px',
                paddingTop: (padding.top || 0) + 'px',
                paddingRight: (padding.right || 0) + 'px',
                paddingBottom: (padding.bottom || 0) + 'px',
                paddingLeft: (padding.left || 0) + 'px',
                position: 'relative'
            };

            // Reverse
            if (config.reverse) {
                containerStyles.flexDirection += '-reverse';
            }

            // Wrap
            if (config.wrap) {
                containerStyles.flexWrap = 'wrap';
                containerStyles.rowGap = config.wrapSpacing + 'px';
            } else {
                containerStyles.flexWrap = 'nowrap';
            }

            // Spacing mode (justify-content)
            if (config.spacingMode && config.spacingMode !== 'packed') {
                containerStyles.justifyContent = SPACING_MODES[config.spacingMode].css;
            } else {
                // Primary alignment
                var justifyMap = {
                    'start': 'flex-start',
                    'center': 'center',
                    'end': 'flex-end'
                };
                containerStyles.justifyContent = justifyMap[config.primaryAlign] || 'flex-start';
            }

            // Counter alignment (align-items)
            var alignMap = {
                'start': 'flex-start',
                'center': 'center',
                'end': 'flex-end',
                'stretch': 'stretch',
                'baseline': 'baseline'
            };
            containerStyles.alignItems = alignMap[config.counterAlign] || 'flex-start';

            // Sizing
            if (config.primarySizing === 'hug') {
                if (config.direction === 'vertical') {
                    containerStyles.height = 'auto';
                } else {
                    containerStyles.width = 'auto';
                }
            } else if (config.primarySizing === 'fill') {
                if (config.direction === 'vertical') {
                    containerStyles.height = '100%';
                    containerStyles.flex = '1';
                } else {
                    containerStyles.width = '100%';
                    containerStyles.flex = '1';
                }
            }

            if (config.counterSizing === 'hug') {
                if (config.direction === 'vertical') {
                    containerStyles.width = 'auto';
                } else {
                    containerStyles.height = 'auto';
                }
            } else if (config.counterSizing === 'fill') {
                if (config.direction === 'vertical') {
                    containerStyles.width = '100%';
                } else {
                    containerStyles.height = '100%';
                }
            }

            // Clip content
            if (config.clipContent) {
                containerStyles.overflow = 'hidden';
            }

            return {
                container: containerStyles
            };
        },

        /**
         * Genera CSS para un hijo de auto layout
         *
         * @param {Object} layoutChild Configuración del hijo
         * @param {Object} parentConfig Configuración del padre
         * @returns {Object}
         */
        generateCSSForChild: function(layoutChild, parentConfig) {
            var styles = {
                position: 'relative'
            };

            // Sizing
            switch (layoutChild.sizing) {
                case 'hug':
                    styles.flex = '0 0 auto';
                    break;
                case 'fixed':
                    styles.flex = '0 0 auto';
                    break;
                case 'fill':
                    styles.flex = layoutChild.fillRatio + ' 1 0%';
                    break;
            }

            // Min/Max constraints
            if (layoutChild.minWidth !== null) {
                styles.minWidth = layoutChild.minWidth + 'px';
            }
            if (layoutChild.maxWidth !== null) {
                styles.maxWidth = layoutChild.maxWidth + 'px';
            }
            if (layoutChild.minHeight !== null) {
                styles.minHeight = layoutChild.minHeight + 'px';
            }
            if (layoutChild.maxHeight !== null) {
                styles.maxHeight = layoutChild.maxHeight + 'px';
            }

            // Align self override
            if (layoutChild.alignSelf !== 'auto') {
                var alignMap = {
                    'start': 'flex-start',
                    'center': 'center',
                    'end': 'flex-end',
                    'stretch': 'stretch'
                };
                styles.alignSelf = alignMap[layoutChild.alignSelf] || 'auto';
            }

            return styles;
        },

        /**
         * Remueve los estilos CSS de auto layout
         *
         * @param {string} elementId ID del elemento
         */
        removeAutoLayoutStyles: function(elementId) {
            var domElement = document.querySelector('[data-element-id="' + elementId + '"]');
            if (!domElement) return;

            // Remover estilos del contenedor
            domElement.style.display = '';
            domElement.style.flexDirection = '';
            domElement.style.gap = '';
            domElement.style.flexWrap = '';
            domElement.style.justifyContent = '';
            domElement.style.alignItems = '';
            domElement.style.rowGap = '';

            domElement.classList.remove('vbp-has-auto-layout');
            delete domElement.dataset.autoLayoutDirection;

            // Remover estilos de hijos
            var children = domElement.querySelectorAll('.vbp-auto-layout-child, .vbp-auto-layout-absolute');
            children.forEach(function(child) {
                child.style.flex = '';
                child.style.alignSelf = '';
                child.style.minWidth = '';
                child.style.maxWidth = '';
                child.style.minHeight = '';
                child.style.maxHeight = '';
                child.classList.remove('vbp-auto-layout-child', 'vbp-auto-layout-absolute');
            });

            // Remover de cache
            this.layoutCache.delete(elementId);
        },

        /**
         * Recalcula el layout de un elemento
         *
         * @param {string} elementId ID del elemento
         */
        recalculateLayout: function(elementId) {
            if (!this.hasAutoLayout(elementId)) return;
            this.applyAutoLayoutStyles(elementId);
        },

        /**
         * Recalcula todos los layouts
         */
        recalculateAllLayouts: function() {
            var self = this;
            this.layoutCache.forEach(function(cachedValue, elementId) {
                self.recalculateLayout(elementId);
            });
        },

        /**
         * Calcula el tamaño "hug" de un elemento
         *
         * @param {string} elementId ID del elemento
         * @returns {Object} { width, height }
         */
        calculateHugSize: function(elementId) {
            var domElement = document.querySelector('[data-element-id="' + elementId + '"]');
            if (!domElement) return { width: 0, height: 0 };

            var autoLayoutConfig = this.getAutoLayout(elementId);
            if (!autoLayoutConfig) return { width: 0, height: 0 };

            var children = domElement.querySelectorAll(':scope > .vbp-auto-layout-child');
            var totalWidth = 0;
            var totalHeight = 0;
            var maxWidth = 0;
            var maxHeight = 0;

            children.forEach(function(child) {
                var rect = child.getBoundingClientRect();
                if (autoLayoutConfig.direction === 'vertical') {
                    totalHeight += rect.height;
                    maxWidth = Math.max(maxWidth, rect.width);
                } else {
                    totalWidth += rect.width;
                    maxHeight = Math.max(maxHeight, rect.height);
                }
            });

            // Añadir spacing
            var numChildren = children.length;
            if (numChildren > 1) {
                if (autoLayoutConfig.direction === 'vertical') {
                    totalHeight += (numChildren - 1) * autoLayoutConfig.spacing;
                } else {
                    totalWidth += (numChildren - 1) * autoLayoutConfig.spacing;
                }
            }

            // Añadir padding
            var padding = autoLayoutConfig.padding || { top: 0, right: 0, bottom: 0, left: 0 };
            totalWidth += (padding.left || 0) + (padding.right || 0);
            totalHeight += (padding.top || 0) + (padding.bottom || 0);
            maxWidth += (padding.left || 0) + (padding.right || 0);
            maxHeight += (padding.top || 0) + (padding.bottom || 0);

            return {
                width: autoLayoutConfig.direction === 'vertical' ? maxWidth : totalWidth,
                height: autoLayoutConfig.direction === 'vertical' ? totalHeight : maxHeight
            };
        },

        /**
         * Reordena los hijos de un elemento con auto layout
         *
         * @param {string} parentId ID del elemento padre
         * @param {Array} newOrder Array de IDs en el nuevo orden
         */
        reorderChildren: function(parentId, newOrder) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var parent = store.getElementDeep(parentId);
            if (!parent || !parent.children) return;

            // Crear mapa de hijos por ID
            var childrenMap = {};
            parent.children.forEach(function(child) {
                childrenMap[child.id] = child;
            });

            // Reordenar
            var newChildren = [];
            newOrder.forEach(function(childId) {
                if (childrenMap[childId]) {
                    newChildren.push(childrenMap[childId]);
                }
            });

            // Actualizar
            store.updateElement(parentId, { children: newChildren });
            this.applyAutoLayoutStyles(parentId);

            // Dispatch event
            document.dispatchEvent(new CustomEvent('vbp:autolayout:reordered', {
                detail: { parentId: parentId, newOrder: newOrder }
            }));
        },

        /**
         * Maneja el inicio de drag
         *
         * @param {Object} detail Detalles del evento
         */
        handleDragStart: function(detail) {
            var elementId = detail.elementId;
            var parent = this.findParentWithAutoLayout(elementId);

            if (parent) {
                var domElement = document.querySelector('[data-element-id="' + parent.id + '"]');
                if (domElement) {
                    domElement.classList.add('vbp-auto-layout-dragging');
                }
            }
        },

        /**
         * Maneja el drag over
         *
         * @param {Object} detail Detalles del evento
         */
        handleDragOver: function(detail) {
            // Mostrar indicador de posición
            this.showDropIndicator(detail);
        },

        /**
         * Maneja el fin de drag
         *
         * @param {Object} detail Detalles del evento
         */
        handleDragEnd: function(detail) {
            // Limpiar indicadores
            this.hideDropIndicator();

            var containers = document.querySelectorAll('.vbp-auto-layout-dragging');
            containers.forEach(function(container) {
                container.classList.remove('vbp-auto-layout-dragging');
            });
        },

        /**
         * Muestra el indicador de drop
         *
         * @param {Object} detail Detalles del evento
         */
        showDropIndicator: function(detail) {
            // Implementación del indicador visual de drop
            var indicator = document.getElementById('vbp-auto-layout-drop-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'vbp-auto-layout-drop-indicator';
                indicator.className = 'vbp-auto-layout-drop-indicator';
                document.body.appendChild(indicator);
            }

            // Posicionar indicador
            if (detail.position) {
                indicator.style.display = 'block';
                indicator.style.left = detail.position.x + 'px';
                indicator.style.top = detail.position.y + 'px';
                indicator.style.width = detail.position.width + 'px';
                indicator.style.height = detail.position.height + 'px';
            }
        },

        /**
         * Oculta el indicador de drop
         */
        hideDropIndicator: function() {
            var indicator = document.getElementById('vbp-auto-layout-drop-indicator');
            if (indicator) {
                indicator.style.display = 'none';
            }
        },

        /**
         * Actualiza los indicadores visuales
         */
        updateIndicators: function() {
            if (!this.indicatorsContainer) return;

            // Limpiar indicadores existentes
            this.indicatorsContainer.innerHTML = '';

            var store = window.Alpine && Alpine.store('vbp');
            if (!store || !store.selection || store.selection.elementIds.length === 0) return;

            var self = this;
            store.selection.elementIds.forEach(function(elementId) {
                if (self.hasAutoLayout(elementId)) {
                    self.renderAutoLayoutIndicators(elementId);
                }
            });
        },

        /**
         * Renderiza los indicadores de auto layout
         *
         * @param {string} elementId ID del elemento
         */
        renderAutoLayoutIndicators: function(elementId) {
            var domElement = document.querySelector('[data-element-id="' + elementId + '"]');
            if (!domElement) return;

            var autoLayoutConfig = this.getAutoLayout(elementId);
            if (!autoLayoutConfig) return;

            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) return;

            var canvasRect = canvas.getBoundingClientRect();
            var elementRect = domElement.getBoundingClientRect();
            var store = window.Alpine && Alpine.store('vbp');
            var zoomLevel = (store && store.zoom ? store.zoom : 100) / 100;

            // Crear contenedor de indicadores para este elemento
            var wrapper = document.createElement('div');
            wrapper.className = 'vbp-auto-layout-indicator-wrapper';
            wrapper.style.position = 'absolute';
            wrapper.style.left = ((elementRect.left - canvasRect.left) / zoomLevel) + 'px';
            wrapper.style.top = ((elementRect.top - canvasRect.top) / zoomLevel) + 'px';
            wrapper.style.width = (elementRect.width / zoomLevel) + 'px';
            wrapper.style.height = (elementRect.height / zoomLevel) + 'px';
            wrapper.style.pointerEvents = 'none';

            // Indicador de dirección
            var directionIndicator = document.createElement('div');
            directionIndicator.className = 'vbp-auto-layout-direction-indicator';
            directionIndicator.innerHTML = autoLayoutConfig.direction === 'vertical' ? '&#x2195;' : '&#x2194;';
            wrapper.appendChild(directionIndicator);

            // Indicadores de padding
            this.renderPaddingIndicators(wrapper, autoLayoutConfig, elementRect, zoomLevel);

            // Indicadores de spacing entre hijos
            this.renderSpacingIndicators(wrapper, elementId, autoLayoutConfig, zoomLevel);

            this.indicatorsContainer.appendChild(wrapper);
        },

        /**
         * Renderiza indicadores de padding
         *
         * @param {HTMLElement} wrapper Contenedor
         * @param {Object} config Configuración
         * @param {DOMRect} rect Rectángulo del elemento
         * @param {number} zoom Nivel de zoom
         */
        renderPaddingIndicators: function(wrapper, config, rect, zoom) {
            var padding = config.padding;

            // Top padding
            if (padding.top > 0) {
                var topIndicator = document.createElement('div');
                topIndicator.className = 'vbp-auto-layout-padding-indicator vbp-auto-layout-padding-top';
                topIndicator.style.height = (padding.top / zoom) + 'px';
                topIndicator.innerHTML = '<span>' + padding.top + '</span>';
                wrapper.appendChild(topIndicator);
            }

            // Right padding
            if (padding.right > 0) {
                var rightIndicator = document.createElement('div');
                rightIndicator.className = 'vbp-auto-layout-padding-indicator vbp-auto-layout-padding-right';
                rightIndicator.style.width = (padding.right / zoom) + 'px';
                rightIndicator.innerHTML = '<span>' + padding.right + '</span>';
                wrapper.appendChild(rightIndicator);
            }

            // Bottom padding
            if (padding.bottom > 0) {
                var bottomIndicator = document.createElement('div');
                bottomIndicator.className = 'vbp-auto-layout-padding-indicator vbp-auto-layout-padding-bottom';
                bottomIndicator.style.height = (padding.bottom / zoom) + 'px';
                bottomIndicator.innerHTML = '<span>' + padding.bottom + '</span>';
                wrapper.appendChild(bottomIndicator);
            }

            // Left padding
            if (padding.left > 0) {
                var leftIndicator = document.createElement('div');
                leftIndicator.className = 'vbp-auto-layout-padding-indicator vbp-auto-layout-padding-left';
                leftIndicator.style.width = (padding.left / zoom) + 'px';
                leftIndicator.innerHTML = '<span>' + padding.left + '</span>';
                wrapper.appendChild(leftIndicator);
            }
        },

        /**
         * Renderiza indicadores de spacing
         *
         * @param {HTMLElement} wrapper Contenedor
         * @param {string} elementId ID del elemento
         * @param {Object} config Configuración
         * @param {number} zoom Nivel de zoom
         */
        renderSpacingIndicators: function(wrapper, elementId, config, zoom) {
            var domElement = document.querySelector('[data-element-id="' + elementId + '"]');
            if (!domElement) return;

            var children = domElement.querySelectorAll(':scope > .vbp-auto-layout-child');
            if (children.length < 2) return;

            var parentRect = domElement.getBoundingClientRect();

            for (var childIndex = 0; childIndex < children.length - 1; childIndex++) {
                var currentChild = children[childIndex];
                var nextChild = children[childIndex + 1];

                var currentRect = currentChild.getBoundingClientRect();
                var nextRect = nextChild.getBoundingClientRect();

                var spacingIndicator = document.createElement('div');
                spacingIndicator.className = 'vbp-auto-layout-spacing-indicator';

                if (config.direction === 'vertical') {
                    var topPosition = (currentRect.bottom - parentRect.top) / zoom;
                    var indicatorHeight = (nextRect.top - currentRect.bottom) / zoom;
                    spacingIndicator.style.top = topPosition + 'px';
                    spacingIndicator.style.left = '50%';
                    spacingIndicator.style.transform = 'translateX(-50%)';
                    spacingIndicator.style.height = indicatorHeight + 'px';
                    spacingIndicator.style.width = '20px';
                } else {
                    var leftPosition = (currentRect.right - parentRect.left) / zoom;
                    var indicatorWidth = (nextRect.left - currentRect.right) / zoom;
                    spacingIndicator.style.left = leftPosition + 'px';
                    spacingIndicator.style.top = '50%';
                    spacingIndicator.style.transform = 'translateY(-50%)';
                    spacingIndicator.style.width = indicatorWidth + 'px';
                    spacingIndicator.style.height = '20px';
                }

                spacingIndicator.innerHTML = '<span>' + config.spacing + '</span>';
                wrapper.appendChild(spacingIndicator);
            }
        },

        /**
         * Maneja actualizaciones de elementos
         *
         * @param {string} elementId ID del elemento
         * @param {Object} changes Cambios realizados
         */
        handleElementUpdate: function(elementId, changes) {
            if (changes.autoLayout) {
                this.applyAutoLayoutStyles(elementId);
                this.updateIndicators();
            }

            if (changes.layoutChild) {
                var parent = this.findParentWithAutoLayout(elementId);
                if (parent) {
                    this.applyAutoLayoutStyles(parent.id);
                }
            }

            if (changes.children) {
                if (this.hasAutoLayout(elementId)) {
                    this.applyAutoLayoutStyles(elementId);
                }
            }
        },

        /**
         * Obtiene los presets disponibles
         *
         * @returns {Object}
         */
        getPresets: function() {
            return AUTO_LAYOUT_PRESETS;
        },

        /**
         * Obtiene las opciones de spacing mode
         *
         * @returns {Object}
         */
        getSpacingModes: function() {
            return SPACING_MODES;
        },

        /**
         * Obtiene las opciones de sizing
         *
         * @returns {Object}
         */
        getSizingOptions: function() {
            return SIZING_OPTIONS;
        },

        /**
         * Obtiene las opciones de alineación
         *
         * @returns {Object}
         */
        getAlignmentOptions: function() {
            return ALIGNMENT_OPTIONS;
        },

        /**
         * Genera CSS exportable para producción
         *
         * @param {string} elementId ID del elemento
         * @returns {string} CSS generado
         */
        exportCSS: function(elementId) {
            var autoLayoutConfig = this.getAutoLayout(elementId);
            if (!autoLayoutConfig) return '';

            var store = window.Alpine && Alpine.store('vbp');
            var element = store.getElementDeep(elementId);
            if (!element) return '';

            var css = [];
            var selector = '.vbp-el-' + elementId;

            // Estilos del contenedor
            var containerStyles = this.generateCSSForAutoLayout(autoLayoutConfig);
            var containerRules = [];

            Object.keys(containerStyles.container).forEach(function(prop) {
                var cssProp = prop.replace(/([A-Z])/g, '-$1').toLowerCase();
                containerRules.push('  ' + cssProp + ': ' + containerStyles.container[prop] + ';');
            });

            css.push(selector + ' {');
            css.push(containerRules.join('\n'));
            css.push('}');

            // Estilos de hijos
            if (element.children) {
                var self = this;
                element.children.forEach(function(child) {
                    var layoutChild = child.layoutChild || self.getDefaultLayoutChild();
                    var childStyles = self.generateCSSForChild(layoutChild, autoLayoutConfig);
                    var childSelector = selector + ' > .vbp-el-' + child.id;

                    var childRules = [];
                    Object.keys(childStyles).forEach(function(prop) {
                        var cssProp = prop.replace(/([A-Z])/g, '-$1').toLowerCase();
                        childRules.push('  ' + cssProp + ': ' + childStyles[prop] + ';');
                    });

                    if (childRules.length > 0) {
                        css.push('');
                        css.push(childSelector + ' {');
                        css.push(childRules.join('\n'));
                        css.push('}');
                    }
                });
            }

            return css.join('\n');
        },

        /**
         * Limpia todos los indicadores
         */
        clearIndicators: function() {
            if (this.indicatorsContainer) {
                this.indicatorsContainer.innerHTML = '';
            }
        }
    };

    /**
     * Flag para evitar registro duplicado de componentes
     */
    var alpineComponentsRegistered = false;

    /**
     * Función para registrar componentes Alpine
     */
    function registerAlpineComponents() {
        if (typeof Alpine === 'undefined') return false;

        // Verificar si ya están registrados usando flag local
        if (alpineComponentsRegistered) return true;

        // Registrar componente vbpAutoLayoutPanel
        Alpine.data('vbpAutoLayoutPanel', function() {
            return vbpAutoLayoutPanelComponent();
        });

        // Registrar componente vbpLayoutChildPanel
        Alpine.data('vbpLayoutChildPanel', function() {
            return vbpLayoutChildPanelComponent();
        });

        alpineComponentsRegistered = true;
        return true;
    }

    // Intentar registrar inmediatamente si Alpine ya existe
    if (typeof Alpine !== 'undefined') {
        registerAlpineComponents();
    }

    // También escuchar alpine:init por si se carga antes
    document.addEventListener('alpine:init', function() {
        registerAlpineComponents();
    });

    /**
     * Componente Alpine para el panel de Auto Layout
     */
    function vbpAutoLayoutPanelComponent() {
        return {
            /**
             * Panel expandido
             */
            expanded: true,

            /**
             * Tab activa (main, padding, advanced)
             */
            activeTab: 'main',

            /**
             * Modo de padding independiente
             */
            independentPadding: false,

            /**
             * Obtiene el elemento seleccionado
             */
            get selectedElement() {
                var store = Alpine.store('vbp');
                if (store.selection.elementIds.length === 1) {
                    return store.getElementDeep(store.selection.elementIds[0]);
                }
                return null;
            },

            /**
             * Verifica si tiene auto layout
             */
            get hasAutoLayout() {
                if (!this.selectedElement) return false;
                return window.VBPAutoLayout.hasAutoLayout(this.selectedElement.id);
            },

            /**
             * Obtiene la configuración actual
             */
            get currentConfig() {
                if (!this.selectedElement) return null;
                return window.VBPAutoLayout.getAutoLayout(this.selectedElement.id);
            },

            /**
             * Agrega auto layout
             */
            addAutoLayout: function() {
                if (!this.selectedElement) return;
                window.VBPAutoLayout.addAutoLayout(this.selectedElement.id);
            },

            /**
             * Remueve auto layout
             */
            removeAutoLayout: function() {
                if (!this.selectedElement) return;
                window.VBPAutoLayout.removeAutoLayout(this.selectedElement.id);
            },

            /**
             * Actualiza un valor
             */
            updateValue: function(property, value) {
                if (!this.selectedElement) return;
                var changes = {};
                changes[property] = value;
                window.VBPAutoLayout.updateAutoLayout(this.selectedElement.id, changes);
            },

            /**
             * Actualiza el padding
             */
            updatePadding: function(side, value) {
                if (!this.selectedElement || !this.currentConfig) return;

                var newPadding = Object.assign({}, this.currentConfig.padding);
                var parsedValue = parseInt(value, 10) || 0;

                if (this.independentPadding) {
                    newPadding[side] = parsedValue;
                } else {
                    // Modo uniforme
                    if (side === 'top' || side === 'bottom') {
                        newPadding.top = parsedValue;
                        newPadding.bottom = parsedValue;
                    } else {
                        newPadding.left = parsedValue;
                        newPadding.right = parsedValue;
                    }
                }

                this.updateValue('padding', newPadding);
            },

            /**
             * Actualiza padding uniforme
             */
            updateUniformPadding: function(value) {
                if (!this.selectedElement) return;
                var parsedValue = parseInt(value, 10) || 0;
                this.updateValue('padding', {
                    top: parsedValue,
                    right: parsedValue,
                    bottom: parsedValue,
                    left: parsedValue
                });
            },

            /**
             * Aplica un preset
             */
            applyPreset: function(presetName) {
                if (!this.selectedElement) return;
                window.VBPAutoLayout.applyPreset(this.selectedElement.id, presetName);
            },

            /**
             * Obtiene los presets disponibles
             */
            get presets() {
                return window.VBPAutoLayout.getPresets();
            },

            /**
             * Obtiene los spacing modes
             */
            get spacingModes() {
                return window.VBPAutoLayout.getSpacingModes();
            },

            /**
             * Obtiene las opciones de sizing
             */
            get sizingOptions() {
                return window.VBPAutoLayout.getSizingOptions();
            },

            /**
             * Obtiene las opciones de alineación
             */
            get alignmentOptions() {
                return window.VBPAutoLayout.getAlignmentOptions();
            },

            /**
             * Cambia la dirección
             */
            toggleDirection: function() {
                if (!this.currentConfig) return;
                var newDirection = this.currentConfig.direction === 'vertical' ? 'horizontal' : 'vertical';
                this.updateValue('direction', newDirection);
            },

            /**
             * Cambia reverse
             */
            toggleReverse: function() {
                if (!this.currentConfig) return;
                this.updateValue('reverse', !this.currentConfig.reverse);
            },

            /**
             * Cambia wrap
             */
            toggleWrap: function() {
                if (!this.currentConfig) return;
                this.updateValue('wrap', !this.currentConfig.wrap);
            },

            /**
             * Cambia clip content
             */
            toggleClipContent: function() {
                if (!this.currentConfig) return;
                this.updateValue('clipContent', !this.currentConfig.clipContent);
            },

            /**
             * Obtiene preset activo
             */
            getActivePreset: function() {
                if (!this.currentConfig) return null;

                var presets = this.presets;
                var config = this.currentConfig;

                for (var presetName in presets) {
                    var preset = presets[presetName];
                    if (preset.direction === config.direction &&
                        preset.spacing === config.spacing &&
                        preset.spacingMode === config.spacingMode) {
                        return presetName;
                    }
                }
                return null;
            },

            /**
             * Exporta CSS
             */
            exportCSS: function() {
                if (!this.selectedElement) return '';
                return window.VBPAutoLayout.exportCSS(this.selectedElement.id);
            }
        };
    }

    // También exponer en window para compatibilidad
    window.vbpAutoLayoutPanel = vbpAutoLayoutPanelComponent;

    /**
     * Componente Alpine para configuración de hijo de Auto Layout
     */
    function vbpLayoutChildPanelComponent() {
        return {
            /**
             * Obtiene el elemento seleccionado
             */
            get selectedElement() {
                var store = Alpine.store('vbp');
                if (store.selection.elementIds.length === 1) {
                    return store.getElementDeep(store.selection.elementIds[0]);
                }
                return null;
            },

            /**
             * Verifica si el padre tiene auto layout
             */
            get parentHasAutoLayout() {
                if (!this.selectedElement) return false;
                var parent = window.VBPAutoLayout.findParentWithAutoLayout(this.selectedElement.id);
                return !!parent;
            },

            /**
             * Obtiene la configuración de layout child
             */
            get layoutChild() {
                if (!this.selectedElement) return null;
                return this.selectedElement.layoutChild || window.VBPAutoLayout.getDefaultLayoutChild();
            },

            /**
             * Actualiza un valor
             */
            updateValue: function(property, value) {
                if (!this.selectedElement) return;
                var changes = {};
                changes[property] = value;
                window.VBPAutoLayout.updateLayoutChild(this.selectedElement.id, changes);
            },

            /**
             * Obtiene las opciones de sizing
             */
            get sizingOptions() {
                return window.VBPAutoLayout.getSizingOptions();
            },

            /**
             * Cambia absolute
             */
            toggleAbsolute: function() {
                if (!this.layoutChild) return;
                this.updateValue('absolute', !this.layoutChild.absolute);
            },

            /**
             * Actualiza posición absoluta
             */
            updateAbsolutePosition: function(side, value) {
                if (!this.layoutChild) return;
                var newPosition = Object.assign({}, this.layoutChild.absolutePosition || {});
                newPosition[side] = value === '' ? null : parseInt(value, 10);
                this.updateValue('absolutePosition', newPosition);
            }
        };
    }

    // También exponer en window para compatibilidad
    window.vbpLayoutChildPanel = vbpLayoutChildPanelComponent;

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.VBPAutoLayout.init();
        });
    } else {
        window.VBPAutoLayout.init();
    }

})();
