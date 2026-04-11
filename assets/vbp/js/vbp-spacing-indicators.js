/**
 * VBP Spacing Indicators - Mostrar distancias entre elementos
 *
 * Muestra la distancia en px entre el elemento seleccionado y otros elementos.
 * Activar con Alt+hover sobre otros elementos o durante el arrastre.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.2.5
 */

(function() {
    'use strict';

    /**
     * Color naranja para los indicadores de spacing
     */
    var SPACING_COLOR = '#f97316';
    var SPACING_COLOR_RGB = '249, 115, 22';

    /**
     * Sistema de indicadores de espaciado
     */
    window.VBPSpacingIndicators = {
        /**
         * Estado del sistema
         */
        enabled: true,

        /**
         * Lista de elementos DOM de indicadores activos
         */
        indicators: [],

        /**
         * Contenedor de los indicadores
         */
        container: null,

        /**
         * ID del elemento actualmente seleccionado
         */
        activeElementId: null,

        /**
         * Inicializa el sistema de spacing indicators
         */
        init: function() {
            this.createContainer();
            this.bindEvents();
        },

        /**
         * Crea el contenedor para los indicadores
         */
        createContainer: function() {
            var existingContainer = document.getElementById('vbp-spacing-indicators');
            if (existingContainer) {
                this.container = existingContainer;
                return;
            }

            var containerElement = document.createElement('div');
            containerElement.className = 'vbp-spacing-container';
            containerElement.id = 'vbp-spacing-indicators';

            var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
            if (canvasWrapper) {
                canvasWrapper.appendChild(containerElement);
                this.container = containerElement;
            }
        },

        /**
         * Vincula los eventos necesarios
         */
        bindEvents: function() {
            var self = this;

            // Alt + hover para mostrar spacing
            document.addEventListener('mousemove', function(event) {
                if (!event.altKey || !self.enabled) {
                    self.clearIndicators();
                    return;
                }
                self.handleHover(event);
            });

            // Escuchar eventos de drag del canvas
            document.addEventListener('vbp:drag:move', function(event) {
                if (self.enabled && event.detail) {
                    self.showDragSpacing(event.detail);
                }
            });

            document.addEventListener('vbp:drag:end', function() {
                self.clearIndicators();
            });

            // Limpiar al soltar Alt
            document.addEventListener('keyup', function(event) {
                if (event.key === 'Alt') {
                    self.clearIndicators();
                }
            });

            // Actualizar cuando cambia la seleccion
            document.addEventListener('vbp:selection:changed', function() {
                self.clearIndicators();
            });
        },

        /**
         * Maneja el hover con Alt presionado
         *
         * @param {MouseEvent} event Evento de mouse
         */
        handleHover: function(event) {
            var store = window.Alpine && Alpine.store('vbp');
            if (!store || !store.selection || store.selection.elementIds.length === 0) {
                return;
            }

            var hoveredElement = event.target.closest('[data-element-id]');
            if (!hoveredElement) {
                this.clearIndicators();
                return;
            }

            var hoveredElementId = hoveredElement.dataset.elementId;
            var selectedElementId = store.selection.elementIds[0];

            // No mostrar distancia al mismo elemento
            if (hoveredElementId === selectedElementId) {
                return;
            }

            this.showSpacing(selectedElementId, hoveredElementId);
        },

        /**
         * Muestra los indicadores de spacing entre dos elementos
         *
         * @param {string} fromElementId ID del elemento origen (seleccionado)
         * @param {string} toElementId   ID del elemento destino (hover)
         */
        showSpacing: function(fromElementId, toElementId) {
            this.clearIndicators();

            var fromElement = document.querySelector('[data-element-id="' + fromElementId + '"]');
            var toElement = document.querySelector('[data-element-id="' + toElementId + '"]');

            if (!fromElement || !toElement) {
                return;
            }

            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) {
                return;
            }

            var canvasRect = canvas.getBoundingClientRect();
            var store = window.Alpine && Alpine.store('vbp');
            var zoomLevel = (store && store.zoom ? store.zoom : 100) / 100;

            var fromRect = fromElement.getBoundingClientRect();
            var toRect = toElement.getBoundingClientRect();

            // Calcular posiciones relativas al canvas (ajustadas por zoom)
            var fromBounds = {
                left: (fromRect.left - canvasRect.left) / zoomLevel,
                right: (fromRect.right - canvasRect.left) / zoomLevel,
                top: (fromRect.top - canvasRect.top) / zoomLevel,
                bottom: (fromRect.bottom - canvasRect.top) / zoomLevel,
                width: fromRect.width / zoomLevel,
                height: fromRect.height / zoomLevel,
                centerX: (fromRect.left - canvasRect.left + fromRect.width / 2) / zoomLevel,
                centerY: (fromRect.top - canvasRect.top + fromRect.height / 2) / zoomLevel
            };

            var toBounds = {
                left: (toRect.left - canvasRect.left) / zoomLevel,
                right: (toRect.right - canvasRect.left) / zoomLevel,
                top: (toRect.top - canvasRect.top) / zoomLevel,
                bottom: (toRect.bottom - canvasRect.top) / zoomLevel,
                width: toRect.width / zoomLevel,
                height: toRect.height / zoomLevel,
                centerX: (toRect.left - canvasRect.left + toRect.width / 2) / zoomLevel,
                centerY: (toRect.top - canvasRect.top + toRect.height / 2) / zoomLevel
            };

            // Calcular distancias
            var distances = this.calculateDistances(fromBounds, toBounds);

            // Renderizar indicadores
            this.renderIndicators(distances, zoomLevel);
        },

        /**
         * Calcula las distancias horizontal y vertical entre dos elementos
         *
         * @param {Object} fromBounds Limites del elemento origen
         * @param {Object} toBounds   Limites del elemento destino
         * @return {Array} Lista de distancias a mostrar
         */
        calculateDistances: function(fromBounds, toBounds) {
            var distances = [];

            // Calcular solapamiento vertical para determinar posicion Y de linea horizontal
            var overlapVerticalStart = Math.max(fromBounds.top, toBounds.top);
            var overlapVerticalEnd = Math.min(fromBounds.bottom, toBounds.bottom);
            var hasVerticalOverlap = overlapVerticalEnd > overlapVerticalStart;

            // Calcular solapamiento horizontal para determinar posicion X de linea vertical
            var overlapHorizontalStart = Math.max(fromBounds.left, toBounds.left);
            var overlapHorizontalEnd = Math.min(fromBounds.right, toBounds.right);
            var hasHorizontalOverlap = overlapHorizontalEnd > overlapHorizontalStart;

            // Distancia horizontal (cuando los elementos estan separados horizontalmente)
            if (fromBounds.right < toBounds.left) {
                // from esta a la izquierda de to
                var horizontalDistance = Math.round(toBounds.left - fromBounds.right);
                if (horizontalDistance > 0) {
                    var lineY = hasVerticalOverlap
                        ? (overlapVerticalStart + overlapVerticalEnd) / 2
                        : (fromBounds.centerY + toBounds.centerY) / 2;

                    distances.push({
                        type: 'horizontal',
                        value: horizontalDistance,
                        x1: fromBounds.right,
                        x2: toBounds.left,
                        y: lineY
                    });
                }
            } else if (toBounds.right < fromBounds.left) {
                // to esta a la izquierda de from
                var horizontalDistanceAlt = Math.round(fromBounds.left - toBounds.right);
                if (horizontalDistanceAlt > 0) {
                    var lineYAlt = hasVerticalOverlap
                        ? (overlapVerticalStart + overlapVerticalEnd) / 2
                        : (fromBounds.centerY + toBounds.centerY) / 2;

                    distances.push({
                        type: 'horizontal',
                        value: horizontalDistanceAlt,
                        x1: toBounds.right,
                        x2: fromBounds.left,
                        y: lineYAlt
                    });
                }
            }

            // Distancia vertical (cuando los elementos estan separados verticalmente)
            if (fromBounds.bottom < toBounds.top) {
                // from esta arriba de to
                var verticalDistance = Math.round(toBounds.top - fromBounds.bottom);
                if (verticalDistance > 0) {
                    var lineX = hasHorizontalOverlap
                        ? (overlapHorizontalStart + overlapHorizontalEnd) / 2
                        : (fromBounds.centerX + toBounds.centerX) / 2;

                    distances.push({
                        type: 'vertical',
                        value: verticalDistance,
                        y1: fromBounds.bottom,
                        y2: toBounds.top,
                        x: lineX
                    });
                }
            } else if (toBounds.bottom < fromBounds.top) {
                // to esta arriba de from
                var verticalDistanceAlt = Math.round(fromBounds.top - toBounds.bottom);
                if (verticalDistanceAlt > 0) {
                    var lineXAlt = hasHorizontalOverlap
                        ? (overlapHorizontalStart + overlapHorizontalEnd) / 2
                        : (fromBounds.centerX + toBounds.centerX) / 2;

                    distances.push({
                        type: 'vertical',
                        value: verticalDistanceAlt,
                        y1: toBounds.bottom,
                        y2: fromBounds.top,
                        x: lineXAlt
                    });
                }
            }

            return distances;
        },

        /**
         * Renderiza los indicadores visuales de distancia
         *
         * @param {Array}  distances Lista de distancias a mostrar
         * @param {number} zoomLevel Nivel de zoom actual
         */
        renderIndicators: function(distances, zoomLevel) {
            if (!this.container) {
                this.createContainer();
            }

            if (!this.container) {
                return;
            }

            var self = this;

            distances.forEach(function(distance) {
                if (distance.value <= 0) {
                    return;
                }

                var indicatorElement = document.createElement('div');
                indicatorElement.className = 'vbp-spacing-indicator vbp-spacing-' + distance.type;

                if (distance.type === 'horizontal') {
                    var lineWidth = (distance.x2 - distance.x1) * zoomLevel;
                    indicatorElement.style.left = (distance.x1 * zoomLevel) + 'px';
                    indicatorElement.style.width = lineWidth + 'px';
                    indicatorElement.style.top = (distance.y * zoomLevel) + 'px';
                } else {
                    var lineHeight = (distance.y2 - distance.y1) * zoomLevel;
                    indicatorElement.style.top = (distance.y1 * zoomLevel) + 'px';
                    indicatorElement.style.height = lineHeight + 'px';
                    indicatorElement.style.left = (distance.x * zoomLevel) + 'px';
                }

                // Crear etiqueta con el valor
                var labelElement = document.createElement('span');
                labelElement.className = 'vbp-spacing-label';
                labelElement.textContent = distance.value + 'px';
                indicatorElement.appendChild(labelElement);

                self.container.appendChild(indicatorElement);
                self.indicators.push(indicatorElement);
            });
        },

        /**
         * Muestra spacing durante el arrastre de un elemento
         *
         * @param {Object} detail Detalles del evento de drag
         */
        showDragSpacing: function(detail) {
            if (!detail || !detail.elementId) {
                return;
            }

            this.clearIndicators();

            var draggedElement = document.querySelector('[data-element-id="' + detail.elementId + '"]');
            if (!draggedElement) {
                return;
            }

            var canvas = document.querySelector('.vbp-canvas');
            if (!canvas) {
                return;
            }

            var canvasRect = canvas.getBoundingClientRect();
            var store = window.Alpine && Alpine.store('vbp');
            var zoomLevel = (store && store.zoom ? store.zoom : 100) / 100;

            var draggedRect = draggedElement.getBoundingClientRect();
            var draggedBounds = {
                left: (draggedRect.left - canvasRect.left) / zoomLevel,
                right: (draggedRect.right - canvasRect.left) / zoomLevel,
                top: (draggedRect.top - canvasRect.top) / zoomLevel,
                bottom: (draggedRect.bottom - canvasRect.top) / zoomLevel,
                width: draggedRect.width / zoomLevel,
                height: draggedRect.height / zoomLevel,
                centerX: (draggedRect.left - canvasRect.left + draggedRect.width / 2) / zoomLevel,
                centerY: (draggedRect.top - canvasRect.top + draggedRect.height / 2) / zoomLevel
            };

            // Encontrar elementos cercanos
            var allElements = canvas.querySelectorAll('[data-element-id]');
            var nearbyElements = [];
            var proximityThreshold = 200; // px

            allElements.forEach(function(element) {
                var elementId = element.dataset.elementId;
                if (elementId === detail.elementId) {
                    return;
                }

                var elementRect = element.getBoundingClientRect();
                var elementBounds = {
                    left: (elementRect.left - canvasRect.left) / zoomLevel,
                    right: (elementRect.right - canvasRect.left) / zoomLevel,
                    top: (elementRect.top - canvasRect.top) / zoomLevel,
                    bottom: (elementRect.bottom - canvasRect.top) / zoomLevel,
                    width: elementRect.width / zoomLevel,
                    height: elementRect.height / zoomLevel,
                    centerX: (elementRect.left - canvasRect.left + elementRect.width / 2) / zoomLevel,
                    centerY: (elementRect.top - canvasRect.top + elementRect.height / 2) / zoomLevel
                };

                // Calcular distancia aproximada
                var horizontalGap = Math.max(0,
                    Math.max(draggedBounds.left - elementBounds.right, elementBounds.left - draggedBounds.right)
                );
                var verticalGap = Math.max(0,
                    Math.max(draggedBounds.top - elementBounds.bottom, elementBounds.top - draggedBounds.bottom)
                );

                var approximateDistance = Math.sqrt(horizontalGap * horizontalGap + verticalGap * verticalGap);

                if (approximateDistance <= proximityThreshold) {
                    nearbyElements.push({
                        id: elementId,
                        bounds: elementBounds,
                        distance: approximateDistance
                    });
                }
            });

            // Ordenar por distancia y tomar los mas cercanos
            nearbyElements.sort(function(a, b) {
                return a.distance - b.distance;
            });

            var closestElements = nearbyElements.slice(0, 4);

            // Mostrar indicadores para los elementos mas cercanos
            var self = this;
            var allDistances = [];

            closestElements.forEach(function(nearby) {
                var distances = self.calculateDistances(draggedBounds, nearby.bounds);
                allDistances = allDistances.concat(distances);
            });

            this.renderIndicators(allDistances, zoomLevel);
        },

        /**
         * Limpia todos los indicadores
         */
        clearIndicators: function() {
            this.indicators.forEach(function(indicator) {
                if (indicator && indicator.parentNode) {
                    indicator.remove();
                }
            });
            this.indicators = [];
        },

        /**
         * Activa/desactiva el sistema de spacing indicators
         *
         * @return {boolean} Nuevo estado
         */
        toggle: function() {
            this.enabled = !this.enabled;
            if (!this.enabled) {
                this.clearIndicators();
            }
            return this.enabled;
        },

        /**
         * Destruye el sistema y limpia recursos
         */
        destroy: function() {
            this.clearIndicators();
            if (this.container && this.container.parentNode) {
                this.container.remove();
            }
            this.container = null;
        }
    };

    /**
     * Inicializar cuando el DOM este listo
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Solo inicializar si estamos en el editor VBP
        if (document.querySelector('.vbp-canvas-wrapper')) {
            VBPSpacingIndicators.init();
        }
    });

    /**
     * Tambien inicializar si Alpine ya esta cargado (para carga dinamica)
     */
    document.addEventListener('alpine:init', function() {
        if (document.querySelector('.vbp-canvas-wrapper') && !VBPSpacingIndicators.container) {
            VBPSpacingIndicators.init();
        }
    });
})();
