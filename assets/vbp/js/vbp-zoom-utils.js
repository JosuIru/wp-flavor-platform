/**
 * VBP Zoom Utilities - Zoom to selection y otras utilidades de zoom
 *
 * Proporciona funcionalidades avanzadas de zoom:
 * - Zoom to Selection: Centrar y hacer zoom en elementos seleccionados
 * - Zoom to Fit All: Encajar todos los elementos en el viewport
 * - Zoom to 100%: Restablecer zoom al 100%
 *
 * Atajos de teclado:
 * - Shift+2: Zoom to Selection
 * - Shift+1: Zoom to 100%
 * - Ctrl+0: Fit All (ya existente, complementado aquí)
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
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
     * Estilos del ghost de arrastre (extraído como constante de módulo)
     */
    var ZOOM_ANIMATION_DURATION = 300;
    var FIT_PADDING_RATIO = 0.1; // 10% de padding
    var MIN_ZOOM_LEVEL = 10;
    var MAX_ZOOM_LEVEL = 200;

    window.VBPZoomUtils = {
        /**
         * Configuración
         */
        animationDuration: ZOOM_ANIMATION_DURATION,
        fitPadding: FIT_PADDING_RATIO,

        /**
         * Estado de animación activa
         */
        isAnimating: false,

        /**
         * Inicializar
         */
        init: function() {
            this.bindShortcuts();
            this.registerCommands();
            vbpLog.log('VBPZoomUtils inicializado');
        },

        /**
         * Zoom to selection (Shift+2)
         * Centra y hace zoom en los elementos seleccionados.
         * Si no hay selección, centra en todo el canvas.
         */
        zoomToSelection: function() {
            var store = Alpine.store('vbp');
            if (!store) {
                vbpLog.warn('Store VBP no disponible');
                return;
            }

            var boundingBox;

            if (store.selection.elementIds.length > 0) {
                boundingBox = this.getSelectionBoundingBox(store.selection.elementIds);
            } else {
                boundingBox = this.getCanvasBoundingBox();
            }

            if (!boundingBox) {
                vbpLog.warn('No se pudo calcular bounding box');
                return;
            }

            this.zoomToBoundingBox(boundingBox);
        },

        /**
         * Obtener bounding box de elementos seleccionados
         *
         * @param {Array} elementIds - IDs de elementos seleccionados
         * @returns {Object|null} Bounding box con x, y, width, height, centerX, centerY
         */
        getSelectionBoundingBox: function(elementIds) {
            var minX = Infinity, minY = Infinity;
            var maxX = -Infinity, maxY = -Infinity;
            var canvas = document.querySelector('.vbp-canvas');

            if (!canvas) {
                return null;
            }

            var canvasRect = canvas.getBoundingClientRect();
            var store = Alpine.store('vbp');
            var currentZoom = store ? store.zoom / 100 : 1;
            var elementsFound = false;

            for (var i = 0; i < elementIds.length; i++) {
                var elementId = elementIds[i];
                var element = document.querySelector('[data-element-id="' + elementId + '"]');

                if (!element) {
                    continue;
                }

                var rect = element.getBoundingClientRect();

                // Convertir a coordenadas del canvas (sin zoom aplicado)
                var elementLeft = (rect.left - canvasRect.left) / currentZoom;
                var elementTop = (rect.top - canvasRect.top) / currentZoom;
                var elementRight = (rect.right - canvasRect.left) / currentZoom;
                var elementBottom = (rect.bottom - canvasRect.top) / currentZoom;

                minX = Math.min(minX, elementLeft);
                minY = Math.min(minY, elementTop);
                maxX = Math.max(maxX, elementRight);
                maxY = Math.max(maxY, elementBottom);

                elementsFound = true;
            }

            if (!elementsFound) {
                return null;
            }

            var boundingWidth = maxX - minX;
            var boundingHeight = maxY - minY;

            return {
                x: minX,
                y: minY,
                width: boundingWidth,
                height: boundingHeight,
                centerX: minX + boundingWidth / 2,
                centerY: minY + boundingHeight / 2
            };
        },

        /**
         * Obtener bounding box de todo el canvas
         *
         * @returns {Object|null} Bounding box de todos los elementos
         */
        getCanvasBoundingBox: function() {
            var store = Alpine.store('vbp');

            if (!store || store.elements.length === 0) {
                return null;
            }

            var allElementIds = store.elements.map(function(element) {
                return element.id;
            });

            return this.getSelectionBoundingBox(allElementIds);
        },

        /**
         * Zoom para encajar un bounding box en el viewport
         *
         * @param {Object} boundingBox - Bounding box a encajar
         */
        zoomToBoundingBox: function(boundingBox) {
            var store = Alpine.store('vbp');
            var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');

            if (!canvasWrapper || !store) {
                return;
            }

            var wrapperRect = canvasWrapper.getBoundingClientRect();
            var viewportWidth = wrapperRect.width;
            var viewportHeight = wrapperRect.height;

            // Calcular zoom óptimo para que el contenido ocupe ~80% del viewport
            var paddingFactor = this.fitPadding;
            var availableWidth = viewportWidth * (1 - paddingFactor * 2);
            var availableHeight = viewportHeight * (1 - paddingFactor * 2);

            // Calcular escala necesaria
            var scaleX = availableWidth / boundingBox.width;
            var scaleY = availableHeight / boundingBox.height;

            // Usar la escala menor para mantener proporciones
            var targetZoom = Math.min(scaleX, scaleY, MAX_ZOOM_LEVEL / 100) * 100;
            targetZoom = Math.max(targetZoom, MIN_ZOOM_LEVEL);
            targetZoom = Math.round(targetZoom);

            // Calcular scroll para centrar el bounding box
            var newZoomFactor = targetZoom / 100;
            var scrollX = (boundingBox.centerX * newZoomFactor) - (viewportWidth / 2);
            var scrollY = (boundingBox.centerY * newZoomFactor) - (viewportHeight / 2);

            // Asegurar que el scroll no sea negativo
            scrollX = Math.max(0, scrollX);
            scrollY = Math.max(0, scrollY);

            // Animar transición
            this.animateZoomAndScroll(targetZoom, scrollX, scrollY);
        },

        /**
         * Animar zoom y scroll suavemente
         *
         * @param {number} targetZoom - Nivel de zoom objetivo (porcentaje)
         * @param {number} targetScrollX - Posición X del scroll objetivo
         * @param {number} targetScrollY - Posición Y del scroll objetivo
         */
        animateZoomAndScroll: function(targetZoom, targetScrollX, targetScrollY) {
            var store = Alpine.store('vbp');
            var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');

            if (!canvasWrapper || !store) {
                return;
            }

            // Evitar animaciones concurrentes
            if (this.isAnimating) {
                return;
            }

            this.isAnimating = true;

            var startZoom = store.zoom;
            var startScrollX = canvasWrapper.scrollLeft;
            var startScrollY = canvasWrapper.scrollTop;
            var startTime = performance.now();
            var duration = this.animationDuration;
            var self = this;

            function animate(currentTime) {
                var elapsed = currentTime - startTime;
                var progress = Math.min(elapsed / duration, 1);

                // Easing: ease-out cubic para sensación natural
                var easedProgress = 1 - Math.pow(1 - progress, 3);

                // Interpolar zoom
                var currentZoom = startZoom + (targetZoom - startZoom) * easedProgress;
                store.zoom = Math.round(currentZoom);

                // Interpolar scroll
                canvasWrapper.scrollLeft = startScrollX + (targetScrollX - startScrollX) * easedProgress;
                canvasWrapper.scrollTop = startScrollY + (targetScrollY - startScrollY) * easedProgress;

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    // Asegurar valores finales exactos
                    store.zoom = targetZoom;
                    canvasWrapper.scrollLeft = targetScrollX;
                    canvasWrapper.scrollTop = targetScrollY;

                    self.isAnimating = false;

                    // Emitir evento de completado
                    document.dispatchEvent(new CustomEvent('vbp:zoom:complete', {
                        detail: { zoom: targetZoom }
                    }));

                    // Mostrar indicador visual
                    self.showZoomIndicator(targetZoom);
                }
            }

            requestAnimationFrame(animate);
        },

        /**
         * Mostrar indicador visual de zoom
         *
         * @param {number} zoomLevel - Nivel de zoom actual
         */
        showZoomIndicator: function(zoomLevel) {
            var existingIndicator = document.getElementById('vbp-zoom-indicator');
            if (existingIndicator) {
                existingIndicator.remove();
            }

            var indicator = document.createElement('div');
            indicator.id = 'vbp-zoom-indicator';
            indicator.innerHTML = 'Zoom: ' + zoomLevel + '%';
            indicator.style.cssText = 'position: fixed; bottom: 20px; right: 20px; padding: 8px 16px; background: rgba(30, 30, 46, 0.9); color: #cdd6f4; border-radius: 6px; font-size: 14px; font-weight: 500; z-index: 10000; pointer-events: none; transition: opacity 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.3);';
            document.body.appendChild(indicator);

            setTimeout(function() {
                indicator.style.opacity = '0';
                setTimeout(function() {
                    if (indicator.parentNode) {
                        indicator.remove();
                    }
                }, 300);
            }, 1000);
        },

        /**
         * Zoom to fit all (encajar todos los elementos)
         */
        zoomToFitAll: function() {
            var boundingBox = this.getCanvasBoundingBox();

            if (boundingBox) {
                this.zoomToBoundingBox(boundingBox);
            } else {
                // Si no hay elementos, ir al 100%
                this.zoomTo100();
            }
        },

        /**
         * Zoom al 100% y centrar
         */
        zoomTo100: function() {
            var store = Alpine.store('vbp');
            var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');

            if (!store || !canvasWrapper) {
                return;
            }

            // Animar a 100% con scroll al inicio
            this.animateZoomAndScroll(100, 0, 0);
        },

        /**
         * Bind shortcuts de teclado
         */
        bindShortcuts: function() {
            var self = this;

            document.addEventListener('keydown', function(event) {
                // Ignorar si estamos en un input/textarea
                var activeElement = document.activeElement;
                if (activeElement && (activeElement.tagName === 'INPUT' ||
                    activeElement.tagName === 'TEXTAREA' ||
                    activeElement.getAttribute('contenteditable') === 'true')) {
                    return;
                }

                // Shift+2: Zoom to Selection
                if (event.shiftKey && !event.ctrlKey && !event.altKey && !event.metaKey && event.key === '2') {
                    event.preventDefault();
                    self.zoomToSelection();
                }

                // Shift+1: Zoom to 100%
                if (event.shiftKey && !event.ctrlKey && !event.altKey && !event.metaKey && event.key === '1') {
                    event.preventDefault();
                    self.zoomTo100();
                }

                // Shift+0: Fit All
                if (event.shiftKey && !event.ctrlKey && !event.altKey && !event.metaKey && event.key === '0') {
                    event.preventDefault();
                    self.zoomToFitAll();
                }
            });
        },

        /**
         * Registrar comandos en la paleta de comandos
         */
        registerCommands: function() {
            // Esperar a que la paleta de comandos esté disponible
            var self = this;

            document.addEventListener('alpine:initialized', function() {
                var commandPalette = Alpine.store('vbpCommandPalette');

                if (!commandPalette) {
                    return;
                }

                // Agregar comandos de zoom a la paleta
                var zoomCommands = [
                    {
                        id: 'zoom-to-selection',
                        label: 'Zoom a Selección',
                        category: 'vista',
                        icon: '🎯',
                        action: 'zoomToSelection',
                        shortcut: 'Shift+2'
                    },
                    {
                        id: 'zoom-to-100-animated',
                        label: 'Zoom 100% (animado)',
                        category: 'vista',
                        icon: '1️⃣',
                        action: 'zoom100Animated',
                        shortcut: 'Shift+1'
                    },
                    {
                        id: 'zoom-fit-all',
                        label: 'Ajustar Todo',
                        category: 'vista',
                        icon: '📐',
                        action: 'zoomFitAll',
                        shortcut: 'Shift+0'
                    }
                ];

                // Si hay método para registrar comandos
                if (commandPalette.commands && Array.isArray(commandPalette.commands)) {
                    zoomCommands.forEach(function(command) {
                        commandPalette.commands.push(command);
                    });
                }
            });

            // Escuchar eventos de ejecución de acciones
            document.addEventListener('vbp:executeAction', function(event) {
                if (!event.detail || !event.detail.action) {
                    return;
                }

                switch (event.detail.action) {
                    case 'zoomToSelection':
                        self.zoomToSelection();
                        break;
                    case 'zoom100Animated':
                        self.zoomTo100();
                        break;
                    case 'zoomFitAll':
                        self.zoomToFitAll();
                        break;
                }
            });
        },

        /**
         * Centrar elemento específico en el viewport
         *
         * @param {string} elementId - ID del elemento a centrar
         * @param {number} [targetZoom] - Zoom opcional (por defecto mantiene el actual)
         */
        centerElement: function(elementId, targetZoom) {
            var boundingBox = this.getSelectionBoundingBox([elementId]);

            if (!boundingBox) {
                vbpLog.warn('Elemento no encontrado: ' + elementId);
                return;
            }

            var store = Alpine.store('vbp');
            var zoom = targetZoom || (store ? store.zoom : 100);

            var canvasWrapper = document.querySelector('.vbp-canvas-wrapper');
            if (!canvasWrapper) {
                return;
            }

            var wrapperRect = canvasWrapper.getBoundingClientRect();
            var viewportWidth = wrapperRect.width;
            var viewportHeight = wrapperRect.height;

            var zoomFactor = zoom / 100;
            var scrollX = (boundingBox.centerX * zoomFactor) - (viewportWidth / 2);
            var scrollY = (boundingBox.centerY * zoomFactor) - (viewportHeight / 2);

            scrollX = Math.max(0, scrollX);
            scrollY = Math.max(0, scrollY);

            this.animateZoomAndScroll(zoom, scrollX, scrollY);
        }
    };

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            VBPZoomUtils.init();
        });
    } else {
        VBPZoomUtils.init();
    }
})();
