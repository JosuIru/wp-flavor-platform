/**
 * VBP Constraints - Sistema de Pinning/Anclaje de elementos
 *
 * Permite anclar elementos a los bordes de su contenedor padre,
 * manteniendo las distancias al redimensionar.
 *
 * Inspirado en el sistema de constraints de Figma.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.2.6
 */

(function() {
    'use strict';

    /**
     * Configuracion de colores para indicadores
     */
    var CONSTRAINT_COLOR = '#3b82f6';
    var CONSTRAINT_COLOR_ACTIVE = '#2563eb';
    var CONSTRAINT_LINE_COLOR = 'rgba(59, 130, 246, 0.6)';

    /**
     * Presets de constraints predefinidos
     */
    var CONSTRAINT_PRESETS = {
        'top-left': {
            horizontal: 'left',
            vertical: 'top',
            label: 'Superior izquierda',
            icon: '&#x2196;'
        },
        'top-center': {
            horizontal: 'center',
            vertical: 'top',
            label: 'Superior centro',
            icon: '&#x2191;'
        },
        'top-right': {
            horizontal: 'right',
            vertical: 'top',
            label: 'Superior derecha',
            icon: '&#x2197;'
        },
        'center-left': {
            horizontal: 'left',
            vertical: 'center',
            label: 'Centro izquierda',
            icon: '&#x2190;'
        },
        'center': {
            horizontal: 'center',
            vertical: 'center',
            label: 'Centro',
            icon: '&#x2B55;'
        },
        'center-right': {
            horizontal: 'right',
            vertical: 'center',
            label: 'Centro derecha',
            icon: '&#x2192;'
        },
        'bottom-left': {
            horizontal: 'left',
            vertical: 'bottom',
            label: 'Inferior izquierda',
            icon: '&#x2199;'
        },
        'bottom-center': {
            horizontal: 'center',
            vertical: 'bottom',
            label: 'Inferior centro',
            icon: '&#x2193;'
        },
        'bottom-right': {
            horizontal: 'right',
            vertical: 'bottom',
            label: 'Inferior derecha',
            icon: '&#x2198;'
        },
        'stretch-horizontal': {
            horizontal: 'stretch',
            vertical: 'top',
            label: 'Estirar horizontal',
            icon: '&#x2194;'
        },
        'stretch-vertical': {
            horizontal: 'left',
            vertical: 'stretch',
            label: 'Estirar vertical',
            icon: '&#x2195;'
        },
        'fill': {
            horizontal: 'stretch',
            vertical: 'stretch',
            label: 'Rellenar',
            icon: '&#x2B1C;'
        }
    };

    /**
     * Sistema de Constraints
     */
    window.VBPConstraints = {
        /**
         * Estado del sistema
         */
        enabled: true,

        /**
         * Contenedor de indicadores visuales
         */
        indicatorsContainer: null,

        /**
         * Cache de constraints por elemento
         */
        constraintsCache: new Map(),

        /**
         * Observador de redimensionamiento
         */
        resizeObserver: null,

        /**
         * Inicializa el sistema de constraints
         */
        init: function() {
            this.createIndicatorsContainer();
            this.bindEvents();
            this.initResizeObserver();
        },

        /**
         * Crea el contenedor para indicadores visuales en canvas
         */
        createIndicatorsContainer: function() {
            var existingContainer = document.getElementById('vbp-constraints-indicators');
            if (existingContainer) {
                this.indicatorsContainer = existingContainer;
                return;
            }

            var containerElement = document.createElement('div');
            containerElement.className = 'vbp-constraints-indicators';
            containerElement.id = 'vbp-constraints-indicators';

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

            // Escuchar cambios de seleccion
            document.addEventListener('vbp:selection:changed', function(event) {
                self.updateIndicators();
            });

            // Escuchar actualizaciones de elementos
            document.addEventListener('vbp:element:updated', function(event) {
                if (event.detail && event.detail.changes && event.detail.changes.styles) {
                    self.handleStyleUpdate(event.detail.id, event.detail.changes.styles);
                }
            });

            // Escuchar redimensionamiento del canvas
            document.addEventListener('vbp:canvas:resized', function() {
                self.applyAllConstraints();
            });

            // Escuchar cambio de breakpoint
            document.addEventListener('vbp:breakpoint:changed', function() {
                self.applyAllConstraints();
            });
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
                    var parentElement = entry.target;
                    if (parentElement.classList.contains('vbp-element') ||
                        parentElement.classList.contains('vbp-canvas-content')) {
                        self.handleParentResize(parentElement);
                    }
                });
            });

            // Observar el canvas content
            var canvasContent = document.querySelector('.vbp-canvas-content');
            if (canvasContent) {
                this.resizeObserver.observe(canvasContent);
            }
        },

        /**
         * Maneja el redimensionamiento de un contenedor padre
         *
         * @param {HTMLElement} parentElement Elemento padre redimensionado
         */
        handleParentResize: function(parentElement) {
            var self = this;
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            // Encontrar elementos hijos con constraints
            var childrenElements = parentElement.querySelectorAll('[data-element-id]');
            childrenElements.forEach(function(childElement) {
                var elementId = childElement.dataset.elementId;
                var element = store.getElementDeep(elementId);
                if (element && self.hasConstraints(element)) {
                    self.applyConstraints(element, parentElement);
                }
            });
        },

        /**
         * Verifica si un elemento tiene constraints configurados
         *
         * @param {Object} element Elemento del store
         * @returns {boolean}
         */
        hasConstraints: function(element) {
            if (!element || !element.styles || !element.styles.constraints) {
                return false;
            }
            var constraintsConfig = element.styles.constraints;
            return constraintsConfig.top || constraintsConfig.right ||
                   constraintsConfig.bottom || constraintsConfig.left ||
                   constraintsConfig.centerH || constraintsConfig.centerV;
        },

        /**
         * Obtiene el preset de constraints activo para un elemento
         *
         * @param {Object} element Elemento del store
         * @returns {string|null} ID del preset o null
         */
        getActivePreset: function(element) {
            if (!element || !element.styles || !element.styles.constraints) {
                return null;
            }

            var constraintsConfig = element.styles.constraints;
            var horizontalConstraint = this.getHorizontalConstraint(constraintsConfig);
            var verticalConstraint = this.getVerticalConstraint(constraintsConfig);

            // Buscar preset que coincida
            for (var presetId in CONSTRAINT_PRESETS) {
                var preset = CONSTRAINT_PRESETS[presetId];
                if (preset.horizontal === horizontalConstraint &&
                    preset.vertical === verticalConstraint) {
                    return presetId;
                }
            }

            return null;
        },

        /**
         * Obtiene el tipo de constraint horizontal
         *
         * @param {Object} constraints Objeto de constraints
         * @returns {string} 'left' | 'right' | 'center' | 'stretch' | 'none'
         */
        getHorizontalConstraint: function(constraints) {
            if (constraints.left && constraints.right) {
                return 'stretch';
            }
            if (constraints.centerH) {
                return 'center';
            }
            if (constraints.right) {
                return 'right';
            }
            if (constraints.left) {
                return 'left';
            }
            return 'none';
        },

        /**
         * Obtiene el tipo de constraint vertical
         *
         * @param {Object} constraints Objeto de constraints
         * @returns {string} 'top' | 'bottom' | 'center' | 'stretch' | 'none'
         */
        getVerticalConstraint: function(constraints) {
            if (constraints.top && constraints.bottom) {
                return 'stretch';
            }
            if (constraints.centerV) {
                return 'center';
            }
            if (constraints.bottom) {
                return 'bottom';
            }
            if (constraints.top) {
                return 'top';
            }
            return 'none';
        },

        /**
         * Aplica un preset de constraints a un elemento
         *
         * @param {string} elementId ID del elemento
         * @param {string} presetId ID del preset
         */
        applyPreset: function(elementId, presetId) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep(elementId);
            if (!element) return;

            var preset = CONSTRAINT_PRESETS[presetId];
            if (!preset) return;

            var newConstraints = {
                top: false,
                right: false,
                bottom: false,
                left: false,
                centerH: false,
                centerV: false,
                topValue: 'auto',
                rightValue: 'auto',
                bottomValue: 'auto',
                leftValue: 'auto'
            };

            // Aplicar horizontal
            switch (preset.horizontal) {
                case 'left':
                    newConstraints.left = true;
                    break;
                case 'right':
                    newConstraints.right = true;
                    break;
                case 'center':
                    newConstraints.centerH = true;
                    break;
                case 'stretch':
                    newConstraints.left = true;
                    newConstraints.right = true;
                    break;
            }

            // Aplicar vertical
            switch (preset.vertical) {
                case 'top':
                    newConstraints.top = true;
                    break;
                case 'bottom':
                    newConstraints.bottom = true;
                    break;
                case 'center':
                    newConstraints.centerV = true;
                    break;
                case 'stretch':
                    newConstraints.top = true;
                    newConstraints.bottom = true;
                    break;
            }

            // Calcular valores actuales
            this.calculateConstraintValues(element, newConstraints);

            // Actualizar elemento
            var styles = JSON.parse(JSON.stringify(element.styles || {}));
            styles.constraints = newConstraints;
            store.updateElement(elementId, { styles: styles });

            // Actualizar indicadores
            this.updateIndicators();
        },

        /**
         * Calcula los valores de distancia para los constraints
         *
         * @param {Object} element Elemento del store
         * @param {Object} constraints Objeto de constraints a actualizar
         */
        calculateConstraintValues: function(element, constraints) {
            var domElement = document.querySelector('[data-element-id="' + element.id + '"]');
            if (!domElement) return;

            var parentElement = domElement.parentElement.closest('.vbp-element') ||
                               domElement.closest('.vbp-canvas-content');
            if (!parentElement) return;

            var elementRect = domElement.getBoundingClientRect();
            var parentRect = parentElement.getBoundingClientRect();

            // Calcular distancias
            if (constraints.top) {
                constraints.topValue = Math.round(elementRect.top - parentRect.top) + 'px';
            }
            if (constraints.right) {
                constraints.rightValue = Math.round(parentRect.right - elementRect.right) + 'px';
            }
            if (constraints.bottom) {
                constraints.bottomValue = Math.round(parentRect.bottom - elementRect.bottom) + 'px';
            }
            if (constraints.left) {
                constraints.leftValue = Math.round(elementRect.left - parentRect.left) + 'px';
            }
        },

        /**
         * Alterna un constraint individual
         *
         * @param {string} elementId ID del elemento
         * @param {string} side 'top' | 'right' | 'bottom' | 'left' | 'centerH' | 'centerV'
         */
        toggleConstraint: function(elementId, side) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep(elementId);
            if (!element) return;

            var styles = JSON.parse(JSON.stringify(element.styles || {}));
            if (!styles.constraints) {
                styles.constraints = {
                    top: false,
                    right: false,
                    bottom: false,
                    left: false,
                    centerH: false,
                    centerV: false,
                    topValue: 'auto',
                    rightValue: 'auto',
                    bottomValue: 'auto',
                    leftValue: 'auto'
                };
            }

            // Alternar el constraint
            styles.constraints[side] = !styles.constraints[side];

            // Manejar exclusiones mutuas
            if (side === 'centerH') {
                if (styles.constraints.centerH) {
                    // Si activamos centerH, desactivar left y right
                    styles.constraints.left = false;
                    styles.constraints.right = false;
                }
            } else if (side === 'centerV') {
                if (styles.constraints.centerV) {
                    // Si activamos centerV, desactivar top y bottom
                    styles.constraints.top = false;
                    styles.constraints.bottom = false;
                }
            } else if (side === 'left' || side === 'right') {
                if (styles.constraints[side]) {
                    // Si activamos left o right, desactivar centerH
                    styles.constraints.centerH = false;
                }
            } else if (side === 'top' || side === 'bottom') {
                if (styles.constraints[side]) {
                    // Si activamos top o bottom, desactivar centerV
                    styles.constraints.centerV = false;
                }
            }

            // Calcular valores si se activa
            if (styles.constraints[side]) {
                this.calculateConstraintValues(element, styles.constraints);
            }

            store.updateElement(elementId, { styles: styles });
            this.updateIndicators();
        },

        /**
         * Aplica los constraints a un elemento cuando su padre se redimensiona
         *
         * @param {Object} element Elemento del store
         * @param {HTMLElement} parentElement Elemento DOM padre
         */
        applyConstraints: function(element, parentElement) {
            var domElement = document.querySelector('[data-element-id="' + element.id + '"]');
            if (!domElement) return;

            var constraints = element.styles.constraints;
            if (!constraints) return;

            var parentRect = parentElement.getBoundingClientRect();

            // Aplicar posicion segun constraints
            var newStyles = {};

            // Constraint izquierdo
            if (constraints.left && constraints.leftValue !== 'auto') {
                newStyles.left = constraints.leftValue;
            }

            // Constraint derecho
            if (constraints.right && constraints.rightValue !== 'auto') {
                newStyles.right = constraints.rightValue;
            }

            // Constraint superior
            if (constraints.top && constraints.topValue !== 'auto') {
                newStyles.top = constraints.topValue;
            }

            // Constraint inferior
            if (constraints.bottom && constraints.bottomValue !== 'auto') {
                newStyles.bottom = constraints.bottomValue;
            }

            // Centro horizontal
            if (constraints.centerH) {
                newStyles.left = '50%';
                newStyles.transform = 'translateX(-50%)';
            }

            // Centro vertical
            if (constraints.centerV) {
                newStyles.top = '50%';
                newStyles.transform = newStyles.transform
                    ? newStyles.transform.replace(')', ' translateY(-50%))')
                    : 'translateY(-50%)';
            }

            // Stretch horizontal
            if (constraints.left && constraints.right) {
                newStyles.width = 'auto';
            }

            // Stretch vertical
            if (constraints.top && constraints.bottom) {
                newStyles.height = 'auto';
            }

            // Aplicar estilos al DOM
            Object.keys(newStyles).forEach(function(prop) {
                domElement.style[prop] = newStyles[prop];
            });
        },

        /**
         * Aplica constraints a todos los elementos
         */
        applyAllConstraints: function() {
            var self = this;
            var store = window.Alpine && Alpine.store('vbp');
            if (!store || !store.elements) return;

            store.elements.forEach(function(element) {
                if (self.hasConstraints(element)) {
                    var domElement = document.querySelector('[data-element-id="' + element.id + '"]');
                    if (domElement) {
                        var parentElement = domElement.parentElement.closest('.vbp-element') ||
                                           domElement.closest('.vbp-canvas-content');
                        if (parentElement) {
                            self.applyConstraints(element, parentElement);
                        }
                    }
                }

                // Procesar hijos recursivamente
                if (element.children && element.children.length > 0) {
                    self.processChildrenConstraints(element.children);
                }
            });
        },

        /**
         * Procesa constraints de elementos hijos recursivamente
         *
         * @param {Array} children Array de elementos hijos
         */
        processChildrenConstraints: function(children) {
            var self = this;
            children.forEach(function(child) {
                if (self.hasConstraints(child)) {
                    var domElement = document.querySelector('[data-element-id="' + child.id + '"]');
                    if (domElement) {
                        var parentElement = domElement.parentElement.closest('.vbp-element') ||
                                           domElement.closest('.vbp-canvas-content');
                        if (parentElement) {
                            self.applyConstraints(child, parentElement);
                        }
                    }
                }
                if (child.children && child.children.length > 0) {
                    self.processChildrenConstraints(child.children);
                }
            });
        },

        /**
         * Actualiza los indicadores visuales en el canvas
         */
        updateIndicators: function() {
            if (!this.indicatorsContainer) return;

            // Limpiar indicadores existentes
            this.indicatorsContainer.innerHTML = '';

            var store = window.Alpine && Alpine.store('vbp');
            if (!store || !store.selection || store.selection.elementIds.length === 0) return;

            var self = this;
            store.selection.elementIds.forEach(function(elementId) {
                var element = store.getElementDeep(elementId);
                if (element && self.hasConstraints(element)) {
                    self.renderConstraintIndicators(element);
                }
            });
        },

        /**
         * Renderiza los indicadores de constraints para un elemento
         *
         * @param {Object} element Elemento del store
         */
        renderConstraintIndicators: function(element) {
            var domElement = document.querySelector('[data-element-id="' + element.id + '"]');
            if (!domElement) return;

            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) return;

            var canvasRect = canvas.getBoundingClientRect();
            var elementRect = domElement.getBoundingClientRect();
            var store = window.Alpine && Alpine.store('vbp');
            var zoomLevel = (store && store.zoom ? store.zoom : 100) / 100;

            var constraints = element.styles.constraints;

            // Calcular posicion relativa al canvas
            var elementBounds = {
                left: (elementRect.left - canvasRect.left) / zoomLevel,
                right: (elementRect.right - canvasRect.left) / zoomLevel,
                top: (elementRect.top - canvasRect.top) / zoomLevel,
                bottom: (elementRect.bottom - canvasRect.top) / zoomLevel,
                centerX: (elementRect.left - canvasRect.left + elementRect.width / 2) / zoomLevel,
                centerY: (elementRect.top - canvasRect.top + elementRect.height / 2) / zoomLevel
            };

            // Obtener bounds del padre
            var parentElement = domElement.parentElement.closest('.vbp-element') ||
                               domElement.closest('.vbp-canvas-content');
            var parentRect = parentElement ? parentElement.getBoundingClientRect() : canvasRect;
            var parentBounds = {
                left: (parentRect.left - canvasRect.left) / zoomLevel,
                right: (parentRect.right - canvasRect.left) / zoomLevel,
                top: (parentRect.top - canvasRect.top) / zoomLevel,
                bottom: (parentRect.bottom - canvasRect.top) / zoomLevel,
                centerX: (parentRect.left - canvasRect.left + parentRect.width / 2) / zoomLevel,
                centerY: (parentRect.top - canvasRect.top + parentRect.height / 2) / zoomLevel
            };

            // Renderizar lineas de constraint
            if (constraints.top) {
                this.renderConstraintLine('top', elementBounds, parentBounds, constraints.topValue);
            }
            if (constraints.right) {
                this.renderConstraintLine('right', elementBounds, parentBounds, constraints.rightValue);
            }
            if (constraints.bottom) {
                this.renderConstraintLine('bottom', elementBounds, parentBounds, constraints.bottomValue);
            }
            if (constraints.left) {
                this.renderConstraintLine('left', elementBounds, parentBounds, constraints.leftValue);
            }
            if (constraints.centerH) {
                this.renderConstraintLine('centerH', elementBounds, parentBounds);
            }
            if (constraints.centerV) {
                this.renderConstraintLine('centerV', elementBounds, parentBounds);
            }
        },

        /**
         * Renderiza una linea de constraint
         *
         * @param {string} side Lado del constraint
         * @param {Object} elementBounds Bounds del elemento
         * @param {Object} parentBounds Bounds del padre
         * @param {string} value Valor de distancia (opcional)
         */
        renderConstraintLine: function(side, elementBounds, parentBounds, value) {
            var lineElement = document.createElement('div');
            lineElement.className = 'vbp-constraint-line vbp-constraint-line--' + side;

            var labelElement = document.createElement('span');
            labelElement.className = 'vbp-constraint-label';

            switch (side) {
                case 'top':
                    lineElement.style.left = elementBounds.centerX + 'px';
                    lineElement.style.top = parentBounds.top + 'px';
                    lineElement.style.height = (elementBounds.top - parentBounds.top) + 'px';
                    labelElement.textContent = value || Math.round(elementBounds.top - parentBounds.top) + 'px';
                    break;

                case 'right':
                    lineElement.style.left = elementBounds.right + 'px';
                    lineElement.style.top = elementBounds.centerY + 'px';
                    lineElement.style.width = (parentBounds.right - elementBounds.right) + 'px';
                    labelElement.textContent = value || Math.round(parentBounds.right - elementBounds.right) + 'px';
                    break;

                case 'bottom':
                    lineElement.style.left = elementBounds.centerX + 'px';
                    lineElement.style.top = elementBounds.bottom + 'px';
                    lineElement.style.height = (parentBounds.bottom - elementBounds.bottom) + 'px';
                    labelElement.textContent = value || Math.round(parentBounds.bottom - elementBounds.bottom) + 'px';
                    break;

                case 'left':
                    lineElement.style.left = parentBounds.left + 'px';
                    lineElement.style.top = elementBounds.centerY + 'px';
                    lineElement.style.width = (elementBounds.left - parentBounds.left) + 'px';
                    labelElement.textContent = value || Math.round(elementBounds.left - parentBounds.left) + 'px';
                    break;

                case 'centerH':
                    lineElement.style.left = parentBounds.centerX + 'px';
                    lineElement.style.top = elementBounds.centerY + 'px';
                    lineElement.style.width = Math.abs(elementBounds.centerX - parentBounds.centerX) + 'px';
                    if (elementBounds.centerX < parentBounds.centerX) {
                        lineElement.style.left = elementBounds.centerX + 'px';
                    }
                    labelElement.textContent = 'H';
                    break;

                case 'centerV':
                    lineElement.style.left = elementBounds.centerX + 'px';
                    lineElement.style.top = parentBounds.centerY + 'px';
                    lineElement.style.height = Math.abs(elementBounds.centerY - parentBounds.centerY) + 'px';
                    if (elementBounds.centerY < parentBounds.centerY) {
                        lineElement.style.top = elementBounds.centerY + 'px';
                    }
                    labelElement.textContent = 'V';
                    break;
            }

            lineElement.appendChild(labelElement);
            this.indicatorsContainer.appendChild(lineElement);
        },

        /**
         * Maneja actualizacion de estilos
         *
         * @param {string} elementId ID del elemento
         * @param {Object} styles Estilos actualizados
         */
        handleStyleUpdate: function(elementId, styles) {
            if (styles.constraints) {
                this.updateIndicators();
            }
        },

        /**
         * Limpia todos los indicadores
         */
        clearIndicators: function() {
            if (this.indicatorsContainer) {
                this.indicatorsContainer.innerHTML = '';
            }
        },

        /**
         * Obtiene los presets disponibles
         *
         * @returns {Object}
         */
        getPresets: function() {
            return CONSTRAINT_PRESETS;
        },

        /**
         * Obtiene el constraint actual de un elemento
         *
         * @param {string} elementId ID del elemento
         * @returns {Object|null}
         */
        getElementConstraints: function(elementId) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return null;

            var element = store.getElementDeep(elementId);
            if (!element || !element.styles || !element.styles.constraints) {
                return null;
            }

            return element.styles.constraints;
        },

        /**
         * Resetea los constraints de un elemento
         *
         * @param {string} elementId ID del elemento
         */
        resetConstraints: function(elementId) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store) return;

            var element = store.getElementDeep(elementId);
            if (!element) return;

            var styles = JSON.parse(JSON.stringify(element.styles || {}));
            styles.constraints = {
                top: false,
                right: false,
                bottom: false,
                left: false,
                centerH: false,
                centerV: false,
                topValue: 'auto',
                rightValue: 'auto',
                bottomValue: 'auto',
                leftValue: 'auto'
            };

            store.updateElement(elementId, { styles: styles });
            this.updateIndicators();
        }
    };

    /**
     * Componente Alpine para el panel de constraints en el inspector
     */
    window.vbpConstraintsPanel = function() {
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
             * Obtiene los constraints actuales
             */
            get currentConstraints() {
                if (!this.selectedElement || !this.selectedElement.styles ||
                    !this.selectedElement.styles.constraints) {
                    return {
                        top: false,
                        right: false,
                        bottom: false,
                        left: false,
                        centerH: false,
                        centerV: false
                    };
                }
                return this.selectedElement.styles.constraints;
            },

            /**
             * Verifica si un constraint esta activo
             */
            isConstraintActive: function(side) {
                return this.currentConstraints[side] || false;
            },

            /**
             * Alterna un constraint
             */
            toggleConstraint: function(side) {
                if (!this.selectedElement) return;
                window.VBPConstraints.toggleConstraint(this.selectedElement.id, side);
            },

            /**
             * Obtiene el preset activo
             */
            get activePreset() {
                if (!this.selectedElement) return null;
                return window.VBPConstraints.getActivePreset(this.selectedElement);
            },

            /**
             * Aplica un preset
             */
            applyPreset: function(presetId) {
                if (!this.selectedElement) return;
                window.VBPConstraints.applyPreset(this.selectedElement.id, presetId);
            },

            /**
             * Obtiene los presets disponibles
             */
            get presets() {
                return window.VBPConstraints.getPresets();
            },

            /**
             * Resetea los constraints
             */
            resetConstraints: function() {
                if (!this.selectedElement) return;
                window.VBPConstraints.resetConstraints(this.selectedElement.id);
            },

            /**
             * Verifica si hay constraints configurados
             */
            get hasAnyConstraint() {
                var constraints = this.currentConstraints;
                return constraints.top || constraints.right || constraints.bottom ||
                       constraints.left || constraints.centerH || constraints.centerV;
            },

            /**
             * Obtiene el texto de estado horizontal
             */
            getHorizontalStatusText: function() {
                var constraints = this.currentConstraints;
                if (constraints.left && constraints.right) {
                    return 'Estirar';
                }
                if (constraints.centerH) {
                    return 'Centro';
                }
                if (constraints.left) {
                    return 'Izquierda';
                }
                if (constraints.right) {
                    return 'Derecha';
                }
                return 'Ninguno';
            },

            /**
             * Obtiene el texto de estado vertical
             */
            getVerticalStatusText: function() {
                var constraints = this.currentConstraints;
                if (constraints.top && constraints.bottom) {
                    return 'Estirar';
                }
                if (constraints.centerV) {
                    return 'Centro';
                }
                if (constraints.top) {
                    return 'Arriba';
                }
                if (constraints.bottom) {
                    return 'Abajo';
                }
                return 'Ninguno';
            }
        };
    };

    // Inicializar cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.VBPConstraints.init();
        });
    } else {
        window.VBPConstraints.init();
    }

})();
